<?php

namespace App\Controllers\Api;

use App\Models\User;
use App\Request\UploadPhotoRequest;
use App\Response\JsonResponse;
use Core\Auth\Auth;
use Core\Http\Request;
use Core\Http\Respond;
use Core\Routing\Controller;
use Core\Valid\Validator;

class PhotoController extends Controller
{
    /**
     * Kept well under Vercel's 4.5 MB request-body ceiling. The browser resizes
     * before uploading, so a real photo arrives at a few hundred kB; this only
     * has to catch the case where that resize did not happen.
     */
    private const MAX_BYTES = 2 * 1024 * 1024;

    private const ALLOWED = [
        'image/webp' => "\x52\x49\x46\x46",
        'image/jpeg' => "\xFF\xD8\xFF",
        'image/png' => "\x89\x50\x4E\x47",
    ];

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    /**
     * The public photo, addressed by access key rather than bearer token.
     *
     * An <img src> cannot send headers, so this one route reads the key from the
     * query string instead of x-access-key and therefore sits outside
     * AuthMiddleware. The key is already printed in the invitation's HTML, so
     * this exposes nothing that was not public already.
     *
     * @param Request $request
     * @return mixed
     */
    public function show(Request $request, Respond $respond)
    {
        $valid = Validator::make(
            ['key' => $request->get('key')],
            ['key' => ['required', 'str', 'trim', 'alpha_num', 'min:49', 'max:50']]
        );

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $user = User::where('access_key', $valid->key)->limit(1)->first();

        if (!$user->exist() || !$user->isActive() || empty($user->photo_couple)) {
            return $this->json->error(Respond::HTTP_NOT_FOUND);
        }

        $bytes = base64_decode(strval($user->photo_couple), true);

        if ($bytes === false) {
            return $this->json->error(Respond::HTTP_NOT_FOUND);
        }

        // Headers are set straight on Respond rather than going through Stream:
        // Stream derives the content type from a filename extension and its map
        // has no webp entry, so a webp would be served as octet-stream and no
        // browser would draw it.
        $respond->getHeader()->set('Content-Type', strval($user->photo_couple_type ?: 'image/webp'));
        $respond->getHeader()->set('Content-Length', strval(strlen($bytes)));

        // Safe for a year because the URL carries the content hash: a new photo
        // is a new URL, so nothing stale is ever served.
        $respond->getHeader()->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $bytes;
    }

    /**
     * @param UploadPhotoRequest $request
     * @return JsonResponse
     */
    public function upload(UploadPhotoRequest $request): JsonResponse
    {
        $valid = $request->validated();

        if ($valid->fails()) {
            return $this->json->errorBadRequest($valid->messages());
        }

        $type = strval($valid->get('type'));

        if (!array_key_exists($type, self::ALLOWED)) {
            return $this->json->errorBadRequest(['Photo must be a webp, jpeg or png image.']);
        }

        $bytes = base64_decode(strval($valid->get('data')), true);

        if ($bytes === false || $bytes === '') {
            return $this->json->errorBadRequest(['Photo data is not valid base64.']);
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            return $this->json->errorBadRequest(['Photo must be smaller than 2MB.']);
        }

        // The declared type is checked against the file's own magic bytes. What
        // the browser claims a file is has never been worth trusting, and this
        // image is served straight back to other people's browsers.
        if (!str_starts_with($bytes, self::ALLOWED[$type])) {
            return $this->json->errorBadRequest(['Photo contents do not match its type.']);
        }

        if (@getimagesizefromstring($bytes) === false) {
            return $this->json->errorBadRequest(['Photo is not a readable image.']);
        }

        $user = Auth::user()->only(['id']);
        $user->photo_couple = base64_encode($bytes);
        $user->photo_couple_type = $type;
        $user->photo_couple_version = substr(hash('sha256', $bytes), 0, 32);
        $user->save();

        return $this->json->successOK(['version' => $user->photo_couple_version]);
    }

    /**
     * @return JsonResponse
     */
    public function destroy(): JsonResponse
    {
        $user = Auth::user()->only(['id']);
        $user->photo_couple = null;
        $user->photo_couple_type = null;
        $user->photo_couple_version = null;
        $user->save();

        return $this->json->successOK(['version' => null]);
    }
}
