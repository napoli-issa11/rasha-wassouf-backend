<?php

namespace App\Services;

use Cloudinary\Cloudinary as CloudinarySdk;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    /**
     * Cache the SDK instance.
     */
    protected static ?CloudinarySdk $client = null;

    /**
     * Get sanitized Cloudinary credentials from configuration or environment.
     */
    public static function getCredentials(): array
    {
        $url = config('filesystems.disks.cloudinary.url') ?: env('CLOUDINARY_URL');
        $cloud = config('filesystems.disks.cloudinary.cloud') ?: env('CLOUDINARY_CLOUD_NAME');
        $key = config('filesystems.disks.cloudinary.key') ?: (env('CLOUDINARY_API_KEY') ?: env('CLOUDINARY_KEY'));
        $secret = config('filesystems.disks.cloudinary.secret') ?: (env('CLOUDINARY_API_SECRET') ?: env('CLOUDINARY_SECRET'));

        // Clean any trailing whitespace or accidental trailing characters (e.g., asterisks)
        if ($secret) {
            $secret = rtrim(trim($secret), '*');
        }
        if ($key) {
            $key = trim($key);
        }
        if ($cloud) {
            $cloud = trim($cloud);
        }
        if ($url) {
            $url = trim($url);
        }

        return [
            'url' => $url,
            'cloud' => $cloud,
            'key' => $key,
            'secret' => $secret,
        ];
    }

    /**
     * Check if Cloudinary credentials are configured.
     * Checks both config (filesystems disk) and environment variables for safety when cached.
     */
    public static function isConfigured(): bool
    {
        $creds = self::getCredentials();
        return !empty($creds['url']) || (!empty($creds['cloud']) && !empty($creds['key']) && !empty($creds['secret']));
    }

    /**
     * Get a properly configured Cloudinary SDK instance.
     * Bypasses strict local SSL verification to prevent local cURL error 60.
     */
    public static function getClient(): CloudinarySdk
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $creds = self::getCredentials();

        if (!empty($creds['url'])) {
            self::$client = new CloudinarySdk($creds['url']);
        } elseif (!empty($creds['cloud']) && !empty($creds['key']) && !empty($creds['secret'])) {
            self::$client = new CloudinarySdk([
                'cloud' => [
                    'cloud_name' => $creds['cloud'],
                    'api_key' => $creds['key'],
                    'api_secret' => $creds['secret'],
                ],
                'url' => [
                    'secure' => true,
                ],
            ]);
        } else {
            // Fall back to facade singleton if available
            self::$client = app(CloudinarySdk::class);
        }

        return self::$client;
    }

    /**
     * Upload an image (UploadedFile, base64 data URI, or file path) directly to Cloudinary.
     * Returns the HTTPS secure Cloudinary URL (string).
     *
     * Local SSL Workaround:
     * Disables strict SSL certificate verification ('verify' => false) to prevent cURL error 60
     * on local development environments (e.g. Windows/XAMPP without system CA bundle).
     *
     * @param mixed $file UploadedFile|string|null
     * @param string $folder Cloudinary destination folder (e.g. 'rasha_portfolio/projects' or 'rasha_portfolio/categories')
     * @param array $options Additional upload parameters
     * @return string|null The secure Cloudinary URL or existing URL/path
     * @throws \RuntimeException|\Throwable
     */
    public static function upload($file, string $folder = 'rasha_portfolio', array $options = []): ?string
    {
        if (empty($file)) {
            return null;
        }

        // Filter out browser-local blob URLs (e.g. blob:http://...) which cannot be processed on server
        if (is_string($file) && str_starts_with($file, 'blob:')) {
            Log::warning("[CloudinaryService] Received browser-local blob URL instead of file data. Ignoring blob URL.");
            return null;
        }

        // If it's already an HTTP/HTTPS URL, preserve it
        if (is_string($file) && (str_starts_with($file, 'http://') || str_starts_with($file, 'https://'))) {
            return $file;
        }

        // If it's an existing static asset path (e.g. /home-imgs/..., /project 01/...) and not a base64 or file
        if (is_string($file) && !str_starts_with($file, 'data:image/') && !file_exists($file)) {
            return $file;
        }

        // Check if this is an explicit file or base64 upload attempt
        $isUploadAttempt = ($file instanceof UploadedFile) || (is_string($file) && str_starts_with($file, 'data:image/'));

        // Verify configuration
        if (!self::isConfigured()) {
            $msg = 'Cloudinary credentials are not configured. Please set CLOUDINARY_URL (or CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, and CLOUDINARY_API_SECRET) in your .env file.';
            Log::error("[CloudinaryService] {$msg}");
            if ($isUploadAttempt) {
                throw new \RuntimeException($msg);
            }
            return is_string($file) ? $file : null;
        }

        try {
            $source = null;

            if ($file instanceof UploadedFile) {
                if (!$file->isValid()) {
                    throw new \RuntimeException('The uploaded image file is invalid: ' . $file->getErrorMessage());
                }
                $source = $file->getRealPath();
            } elseif (is_string($file) && (str_starts_with($file, 'data:image/') || file_exists($file))) {
                $source = $file;
            }

            if ($source) {
                $client = self::getClient();

                // Merge options with local SSL bypass (verify => false) and folder destination
                $uploadOptions = array_merge([
                    'folder' => $folder,
                    'resource_type' => 'image',
                    'verify' => false, // Local SSL Workaround: prevents cURL error 60 on local environment
                ], $options);

                $response = $client->uploadApi()->upload($source, $uploadOptions);

                $secureUrl = $response['secure_url'] ?? $response['url'] ?? null;
                if ($secureUrl) {
                    Log::info("[CloudinaryService] Uploaded image to Cloudinary successfully: {$secureUrl}");
                    return $secureUrl;
                }

                throw new \RuntimeException('Cloudinary upload completed but no secure URL was returned in the response.');
            }
        } catch (\Throwable $e) {
            Log::error("[CloudinaryService] Cloudinary upload failed: " . $e->getMessage(), [
                'folder' => $folder,
                'exception' => $e,
            ]);
            throw new \RuntimeException('Cloudinary upload failed: ' . $e->getMessage(), 0, $e);
        }

        return is_string($file) ? $file : null;
    }

    /**
     * Delete an asset from Cloudinary using its secure URL or public ID.
     */
    public static function delete(?string $urlOrPublicId): bool
    {
        if (empty($urlOrPublicId) || !self::isConfigured()) {
            return false;
        }

        try {
            $publicId = $urlOrPublicId;
            if (str_starts_with($urlOrPublicId, 'http')) {
                $path = parse_url($urlOrPublicId, PHP_URL_PATH);
                if (preg_match('/\/upload\/(?:v\d+\/)?(.+?)(?:\.[a-zA-Z0-9]+)?$/', $path, $matches)) {
                    $publicId = $matches[1];
                }
            }

            $client = self::getClient();
            $result = $client->uploadApi()->destroy($publicId, [
                'verify' => false, // Local SSL Workaround
            ]);

            return ($result['result'] ?? '') === 'ok';
        } catch (\Throwable $e) {
            Log::warning("[CloudinaryService] Cloudinary deletion error: " . $e->getMessage());
            return false;
        }
    }
}
