<?php

namespace App\Controllers\Api;

use App\Repositories\CommentContract;
use App\Repositories\LikeContract;
use App\Repositories\UserContract;
use App\Request\UpdateUserRequest;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Request;
use Core\Http\Stream;
use Core\Support\Time;
use Core\Valid\Hash;
use DateTimeZone;

class DashboardController extends Controller
{
    private const THEME_FONTS = ['default', 'elegant', 'modern', 'classic', 'script'];

    // None of the Latin faces carry Arabic glyphs, so the Arabic script gets its
    // own list rather than sharing one picker that cannot serve both.
    private const THEME_FONTS_ARABIC = ['default', 'naskh', 'amiri', 'cairo', 'tajawal', 'kufi'];

    private const THEME_DIRECTIONS = ['auto', 'ltr', 'rtl'];

    private const BOOLEAN_FIELDS = [
        'filter' => 'is_filter',
        'confetti_animation' => 'is_confetti_animation',
        'can_edit' => 'can_edit',
        'can_delete' => 'can_delete',
        'can_reply' => 'can_reply',
        'show_home' => 'show_home',
        'show_bride' => 'show_bride',
        'show_wedding_date' => 'show_wedding_date',
        'show_gallery' => 'show_gallery',
        'show_comment' => 'show_comment',
        'show_story' => 'show_story',
        'show_gift' => 'show_gift',
        'show_dresscode' => 'show_dresscode',
    ];

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    public function stats(CommentContract $comment, LikeContract $like): JsonResponse
    {
        $comments = $comment->countPresenceByUserID(Auth::id());

        return $this->json->successOK([
            'present' => intval($comments->present_count ?? 0),
            'absent' => intval($comments->absent_count ?? 0),
            'likes' => $like->countLikeByUserID(Auth::id()),
            'comments' => $comment->countCommentByUserID(Auth::id())
        ]);
    }

    public function rotate(UserContract $userContract): JsonResponse
    {
        $status = $userContract->generateNewAccessKey(Auth::id());

        if ($status === 1) {
            return $this->json->successStatusTrue();
        }

        return $this->json->errorServer();
    }

    public function user(): JsonResponse
    {
        // photo_couple is the base64 image itself, a few hundred kB. The
        // dashboard only needs to know whether one exists and which one,
        // which is what the version is for.
        return $this->json->successOK(Auth::user()->except(['id', 'password', 'is_admin', 'is_active', 'created_at', 'updated_at', 'photo_couple', 'photo_couple_type']));
    }

    public function configV2(): JsonResponse
    {
        return $this->json->successOK(Auth::user()->only(['tz', 'can_edit', 'can_delete', 'can_reply', 'tenor_key', 'is_confetti_animation', 'show_home', 'show_bride', 'show_wedding_date', 'show_gallery', 'show_comment', 'show_story', 'show_gift', 'show_dresscode', 'theme_primary_color', 'theme_secondary_color', 'theme_background_color', 'theme_text_color', 'theme_font', 'theme_font_arabic', 'theme_direction', 'theme_divider_color', 'is_custom_theme', 'rsvp_deadline', 'photo_couple_version']));
    }

    public function update(UpdateUserRequest $request): JsonResponse
    {
        $valid = $request->validated();

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $user = Auth::user()->only(['id', 'password']);

        if (!empty($valid->name)) {
            $user->name = $valid->name;
        }

        if (!empty($valid->tz)) {
            if (!in_array($valid->tz, DateTimeZone::listIdentifiers())) {
                return $this->json->errorBadRequest(['Invalid time zone']);
            }

            $user->tz = $valid->tz;
        }

        if (array_key_exists('tenor_key', $request->all())) {
            $user->tenor_key = $valid->tenor_key;
        }

        // Request key => column. Two of them are named differently on the model,
        // the rest match; a table beats thirteen near-identical if-blocks and
        // means a new switch costs one line here rather than five.
        foreach (self::BOOLEAN_FIELDS as $key => $column) {
            if ($valid->get($key) !== null) {
                $user->{$column} = boolval($valid->get($key));
            }
        }

        foreach (['theme_primary_color', 'theme_secondary_color', 'theme_background_color', 'theme_text_color'] as $field) {
            if (!empty($valid->get($field))) {
                if (!preg_match('/^#[0-9a-fA-F]{6}$/', $valid->get($field))) {
                    return $this->json->errorBadRequest([sprintf('%s must be a valid hex color.', $field)]);
                }

                $user->{$field} = strtolower($valid->get($field));
            }
        }

        // Cleared rather than merely absent: an empty divider colour means
        // "follow the text colour", which is how it has always been painted.
        if (array_key_exists('theme_divider_color', $request->all())) {
            $divider = $valid->get('theme_divider_color');

            if (empty($divider)) {
                $user->theme_divider_color = null;
            } elseif (!preg_match('/^#[0-9a-fA-F]{6}$/', $divider)) {
                return $this->json->errorBadRequest(['theme_divider_color must be a valid hex color.']);
            } else {
                $user->theme_divider_color = strtolower($divider);
            }
        }

        if (!empty($valid->get('theme_font'))) {
            if (!in_array($valid->get('theme_font'), self::THEME_FONTS)) {
                return $this->json->errorBadRequest(['Invalid theme font.']);
            }

            $user->theme_font = $valid->get('theme_font');
        }

        if (!empty($valid->get('theme_font_arabic'))) {
            if (!in_array($valid->get('theme_font_arabic'), self::THEME_FONTS_ARABIC)) {
                return $this->json->errorBadRequest(['Invalid Arabic theme font.']);
            }

            $user->theme_font_arabic = $valid->get('theme_font_arabic');
        }

        if (!empty($valid->get('theme_direction'))) {
            if (!in_array($valid->get('theme_direction'), self::THEME_DIRECTIONS)) {
                return $this->json->errorBadRequest(['Invalid text direction.']);
            }

            $user->theme_direction = $valid->get('theme_direction');
        }

        if ($valid->get('is_custom_theme') !== null) {
            $user->is_custom_theme = boolval($valid->get('is_custom_theme'));
        }

        if (array_key_exists('rsvp_deadline', $request->all())) {
            $deadline = $valid->get('rsvp_deadline');

            if (empty($deadline)) {
                // Clearing the deadline leaves the RSVP open indefinitely.
                $user->rsvp_deadline = null;
            } else {
                $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $deadline);

                if (!$date || $date->format('Y-m-d') !== $deadline) {
                    return $this->json->errorBadRequest(['rsvp_deadline must be a valid date (YYYY-MM-DD).']);
                }

                $user->rsvp_deadline = $deadline;
            }
        }

        if (!empty($valid->get('old_password')) && !empty($valid->get('new_password'))) {
            if (!Hash::check($valid->get('old_password'), $user->password ?? '')) {
                return $this->json->errorBadRequest(['password not match.']);
            }

            $user->password = Hash::make($valid->get('new_password'));
        }

        $status = $user->save();
        if ($status <= 1) {
            return $this->json->successStatusTrue();
        }

        return $this->json->errorServer();
    }

    public function download(Stream $stream, CommentContract $comment): Stream
    {
        $streamResource = $stream->getStream();

        fputcsv($streamResource, [
            'uuid',
            'like',
            'name',
            'presence',
            'is_admin',
            'comment',
            'gif_url',
            'ip_address',
            'user_agent',
            'created_at',
            'parent_id',
        ]);

        foreach ($comment->downloadCommentByUserID(Auth::id()) as $value) {
            $value->insert_at = Time::factory($value->insert_at)->tz(auth()->user()->getTimezone());

            $data = array_map(function (mixed $value): mixed {
                if (is_bool($value)) {
                    return $value ? 'True' : 'False';
                }

                if (is_null($value)) {
                    return 'Null';
                }

                return $value;
            }, array_values(get_object_vars($value)));

            fputcsv($streamResource, $data);
        }

        return $stream->create(sprintf('backup_comments_%s.csv', now('y-m-d_H:i:s')))->download();
    }
}
