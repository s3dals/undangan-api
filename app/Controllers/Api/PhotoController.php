<?php

namespace App\Controllers\Api;

use App\Models\Gallery;
use App\Response\JsonResponse;
use App\Support\SupabaseStorage;
use Core\Auth\Auth;
use Core\Routing\Controller;
use Core\Http\Request;
use Core\File\UploadedFile;

class PhotoController extends Controller
{
    private const SLOTS = ['home', 'bride', 'groom'];
    private const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp'];
    private const MAX_SIZE = 5 * 1024 * 1024;

    private $json;

    public function __construct(JsonResponse $json)
    {
        $this->json = $json;
    }

    /**
     * @param UploadedFile|null $file
     * @return string[]|null Validation errors, or null if the file is valid.
     */
    private function validateFile(UploadedFile|null $file): array|null
    {
        if (!$file || !$file->exists()) {
            return ['Photo file is required.'];
        }

        if (!in_array($file->extension(), self::ALLOWED_MIME, true)) {
            return ['Photo must be a jpeg, png, or webp image.'];
        }

        if ($file->getSize() > self::MAX_SIZE) {
            return ['Photo must be smaller than 5MB.'];
        }

        return null;
    }

    public function uploadSlot(Request $request): JsonResponse
    {
        $type = $request->get('type');
        if (!in_array($type, self::SLOTS, true)) {
            return $this->json->errorBadRequest(['Invalid photo type.']);
        }

        $file = $request->file('photo');
        $errors = $this->validateFile($file);
        if ($errors) {
            return $this->json->errorBadRequest($errors);
        }

        $contents = file_get_contents($file->getPathname());
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = sprintf('users/%d/%s-%s.%s', Auth::id(), $type, $file->hashName(), $ext);

        $url = SupabaseStorage::upload($path, $contents, $file->extension());
        if (!$url) {
            return $this->json->errorServer();
        }

        $user = Auth::user()->only(['id']);
        $field = 'photo_' . $type . '_url';
        $user->{$field} = $url;
        $user->save();

        return $this->json->successOK(['url' => $url]);
    }

    public function uploadGallery(Request $request): JsonResponse
    {
        $file = $request->file('photo');
        $errors = $this->validateFile($file);
        if ($errors) {
            return $this->json->errorBadRequest($errors);
        }

        $contents = file_get_contents($file->getPathname());
        $ext = $file->getClientOriginalExtension() ?: 'jpg';
        $path = sprintf('users/%d/gallery-%s.%s', Auth::id(), $file->hashName(), $ext);

        $url = SupabaseStorage::upload($path, $contents, $file->extension());
        if (!$url) {
            return $this->json->errorServer();
        }

        $gallery = Gallery::create([
            'user_id' => Auth::id(),
            'url' => $url,
            'storage_path' => $path,
        ]);

        return $this->json->successOK($gallery->only(['id', 'url']));
    }

    public function listGallery(): JsonResponse
    {
        $galleries = Gallery::where('user_id', Auth::id())->orderBy('id', 'ASC')->get();

        $result = [];
        foreach ($galleries as $gallery) {
            $result[] = ['id' => $gallery->id, 'url' => $gallery->url];
        }

        return $this->json->successOK($result);
    }

    public function deleteGallery(string $id): JsonResponse
    {
        $gallery = Gallery::where('id', $id)->where('user_id', Auth::id())->first();
        if (!$gallery->exist()) {
            return $this->json->errorNotFound();
        }

        SupabaseStorage::delete($gallery->storage_path);
        $gallery->destroy();

        return $this->json->successStatusTrue();
    }
}
