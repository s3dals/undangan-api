<?php

namespace App\Controllers\Api;

use App\Models\Content;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Request;

class ContentController extends Controller
{
    private const MAX_KEYS = 100;
    private const MAX_KEY_LENGTH = 50;
    private const MAX_VALUE_LENGTH = 5000;

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    /**
     * Every stored text for this invitation, as a flat key => value map.
     *
     * @return array
     */
    private function map(): array
    {
        $rows = Content::where('user_id', Auth::id())->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->content_key] = $row->content_value;
        }

        return $result;
    }

    public function index(): JsonResponse
    {
        return $this->json->successOK($this->map());
    }

    public function update(Request $request): JsonResponse
    {
        $items = $request->get('contents');

        if (!is_array($items) || count($items) === 0) {
            return $this->json->errorBadRequest(['contents must be a non-empty object.']);
        }

        if (count($items) > self::MAX_KEYS) {
            return $this->json->errorBadRequest([sprintf('At most %d texts can be saved at once.', self::MAX_KEYS)]);
        }

        // Validate everything before writing anything, so a bad key cannot leave
        // the invitation half updated.
        foreach ($items as $key => $value) {
            if (!is_string($key) || !preg_match('/^[a-z0-9_]{1,' . self::MAX_KEY_LENGTH . '}$/', $key)) {
                return $this->json->errorBadRequest([sprintf('Invalid text name: %s.', is_string($key) ? $key : 'unknown')]);
            }

            if ($value !== null && !is_string($value)) {
                return $this->json->errorBadRequest([sprintf('%s must be text.', $key)]);
            }

            if (is_string($value) && strlen($value) > self::MAX_VALUE_LENGTH) {
                return $this->json->errorBadRequest([sprintf('%s is longer than %d characters.', $key, self::MAX_VALUE_LENGTH)]);
            }
        }

        foreach ($items as $key => $value) {
            $existing = Content::where('user_id', Auth::id())->where('content_key', $key)->first();

            if ($existing->exist()) {
                $existing->content_value = $value;
                $existing->save();
                continue;
            }

            Content::create([
                'user_id' => Auth::id(),
                'content_key' => $key,
                'content_value' => $value,
            ]);
        }

        return $this->json->successOK($this->map());
    }
}
