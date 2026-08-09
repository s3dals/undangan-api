<?php

namespace App\Support;

use Throwable;

final class SupabaseStorage
{
    /**
     * @param string $path
     * @param string $contents
     * @param string $mimeType
     * @return string|null
     */
    public static function upload(string $path, string $contents, string $mimeType): string|null
    {
        $base = rtrim(strval(env('SUPABASE_URL', '')), '/');
        $key = env('SUPABASE_SERVICE_KEY');
        $bucket = env('SUPABASE_BUCKET', 'photos');

        if (empty($base) || empty($key)) {
            return null;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, sprintf('%s/storage/v1/object/%s/%s', $base, $bucket, $path));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $contents);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $key,
                'Content-Type: ' . $mimeType,
                'x-upsert: true',
            ]);

            $response = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($response === false || $status < 200 || $status >= 300) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return sprintf('%s/storage/v1/object/public/%s/%s', $base, $bucket, $path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        $base = rtrim(strval(env('SUPABASE_URL', '')), '/');
        $key = env('SUPABASE_SERVICE_KEY');
        $bucket = env('SUPABASE_BUCKET', 'photos');

        if (empty($base) || empty($key)) {
            return false;
        }

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, sprintf('%s/storage/v1/object/%s/%s', $base, $bucket, $path));
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $key]);

            curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $status >= 200 && $status < 300;
        } catch (Throwable) {
            return false;
        }
    }
}
