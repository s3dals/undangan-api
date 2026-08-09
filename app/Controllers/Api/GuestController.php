<?php

namespace App\Controllers\Api;

use App\Models\Guest;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Request;
use Core\Support\Time;
use Core\Valid\Hash;

class GuestController extends Controller
{
    private const STATUS_PENDING = 'pending';
    private const STATUS_ATTENDING = 'attending';
    private const STATUS_DECLINED = 'declined';

    private const MAX_PARTY_SIZE = 20;

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    /**
     * The deadline is a plain date meaning "the whole of this day", so it is
     * compared in the invitation owner's timezone rather than the server's.
     *
     * @return bool
     */
    private function isRsvpOpen(): bool
    {
        $deadline = Auth::user()->rsvp_deadline;

        if (empty($deadline)) {
            return true;
        }

        return Time::factory()->tz(Auth::user()->getTimezone() ?? 'UTC')->format('Y-m-d') <= $deadline;
    }

    /**
     * Result rows are hydrated as stdClass when iterating a collection, but as
     * a Model from first()/create() - accept both.
     *
     * @param object $guest
     * @return array
     */
    private function serialize(object $guest): array
    {
        return [
            'id' => intval($guest->id),
            'name' => $guest->name,
            'token' => $guest->token,
            'max_guests' => intval($guest->max_guests),
            'status' => $guest->status,
            'guest_count' => intval($guest->guest_count),
            'responded_at' => $guest->responded_at,
        ];
    }

    public function index(): JsonResponse
    {
        $guests = Guest::where('user_id', Auth::id())->orderBy('id', 'ASC')->get();

        $result = [];
        foreach ($guests as $guest) {
            $result[] = $this->serialize($guest);
        }

        return $this->json->successOK($result);
    }

    public function create(Request $request): JsonResponse
    {
        $valid = $this->validate($request, [
            'name' => ['required', 'str', 'trim', 'min:1', 'max:100'],
            'max_guests' => ['nullable', 'int'],
        ]);

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $max = intval($valid->get('max_guests') ?? 1);
        if ($max < 1 || $max > self::MAX_PARTY_SIZE) {
            return $this->json->errorBadRequest([sprintf('max_guests must be between 1 and %d.', self::MAX_PARTY_SIZE)]);
        }

        $guest = Guest::create([
            'user_id' => Auth::id(),
            'name' => $valid->get('name'),
            'token' => Hash::rand(8),
            'max_guests' => $max,
            'status' => self::STATUS_PENDING,
            'guest_count' => 0,
        ]);

        return $this->json->successOK($this->serialize($guest));
    }

    public function update(string $id, Request $request): JsonResponse
    {
        $valid = $this->validate($request, [
            'name' => ['nullable', 'str', 'trim', 'min:1', 'max:100'],
            'max_guests' => ['nullable', 'int'],
        ]);

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $guest = Guest::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$guest->exist()) {
            return $this->json->errorNotFound();
        }

        if (!empty($valid->get('name'))) {
            $guest->name = $valid->get('name');
        }

        if ($valid->get('max_guests') !== null) {
            $max = intval($valid->get('max_guests'));
            if ($max < 1 || $max > self::MAX_PARTY_SIZE) {
                return $this->json->errorBadRequest([sprintf('max_guests must be between 1 and %d.', self::MAX_PARTY_SIZE)]);
            }

            $guest->max_guests = $max;

            if (intval($guest->guest_count) > $max) {
                $guest->guest_count = $max;
            }
        }

        $guest->save();

        // save() strips non-fillable attributes (including id), so re-read to
        // return the persisted row rather than the mutated in-memory model.
        return $this->json->successOK($this->serialize(
            Guest::where('id', $id)->where('user_id', Auth::id())->first()
        ));
    }

    public function destroy(string $id): JsonResponse
    {
        $guest = Guest::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$guest->exist()) {
            return $this->json->errorNotFound();
        }

        $guest->destroy();

        return $this->json->successStatusTrue();
    }

    public function show(string $token): JsonResponse
    {
        $guest = Guest::where('token', $token)->where('user_id', Auth::id())->first();
        if (!$guest->exist()) {
            return $this->json->errorNotFound();
        }

        return $this->json->successOK([
            'name' => $guest->name,
            'max_guests' => intval($guest->max_guests),
            'status' => $guest->status,
            'guest_count' => intval($guest->guest_count),
            'rsvp_deadline' => Auth::user()->rsvp_deadline,
            'can_respond' => $this->isRsvpOpen(),
        ]);
    }

    public function rsvp(string $token, Request $request): JsonResponse
    {
        $valid = $this->validate($request, [
            'attending' => ['bool'],
            'guest_count' => ['nullable', 'int'],
        ]);

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $guest = Guest::where('token', $token)->where('user_id', Auth::id())->first();
        if (!$guest->exist()) {
            return $this->json->errorNotFound();
        }

        // Applies to changing an existing answer as much as to a first reply.
        if (!$this->isRsvpOpen()) {
            return $this->json->errorBadRequest([
                sprintf('The deadline to respond was %s.', Auth::user()->rsvp_deadline),
            ]);
        }

        $attending = boolval($valid->get('attending'));
        $count = 0;

        if ($attending) {
            $count = intval($valid->get('guest_count') ?? 1);

            if ($count < 1 || $count > intval($guest->max_guests)) {
                return $this->json->errorBadRequest([sprintf('guest_count must be between 1 and %d.', intval($guest->max_guests))]);
            }
        }

        $guest->status = $attending ? self::STATUS_ATTENDING : self::STATUS_DECLINED;
        $guest->guest_count = $count;
        $guest->responded_at = Time::factory()->format('Y-m-d H:i:s');
        $guest->save();

        return $this->json->successOK([
            'name' => $guest->name,
            'max_guests' => intval($guest->max_guests),
            'status' => $guest->status,
            'guest_count' => intval($guest->guest_count),
            'rsvp_deadline' => Auth::user()->rsvp_deadline,
            'can_respond' => $this->isRsvpOpen(),
        ]);
    }
}
