<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UploadController extends Controller
{
    /**
     * Upload an image directly to Cloudinary.
     * Accessible by authenticated Admin or general uploads.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'nullable',
            'file' => 'nullable',
            'coverImage' => 'nullable',
            'cover_image' => 'nullable',
            'photo' => 'nullable',
            'folder' => 'nullable|string',
        ]);

        $file = $request->file('image') 
            ?? $request->file('file') 
            ?? $request->file('coverImage') 
            ?? $request->file('cover_image') 
            ?? $request->file('photo') 
            ?? $request->input('image') 
            ?? $request->input('file')
            ?? $request->input('coverImage')
            ?? $request->input('cover_image')
            ?? $request->input('photo');

        if (!$file) {
            return response()->json([
                'status' => 'error',
                'message' => 'No image file or data provided for upload.',
            ], 422);
        }

        $folder = $request->input('folder', 'rasha_portfolio/uploads');

        try {
            $secureUrl = CloudinaryService::upload($file, $folder);

            if (!$secureUrl) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cloudinary upload failed: No secure URL returned.',
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'url' => $secureUrl,
                'secure_url' => $secureUrl,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('UploadController Cloudinary upload error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload error: ' . $e->getMessage(),
            ], 500);
        }
    }
}
