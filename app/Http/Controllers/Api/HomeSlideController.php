<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HomeSlide;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class HomeSlideController extends Controller
{
    /**
     * Display all hero slides.
     */
    public function index(): JsonResponse
    {
        $slides = HomeSlide::orderBy('sort_order', 'asc')->latest()->get();
        return response()->json($slides);
    }

    /**
     * Create a new hero slide (Admin CMS).
     * Uploads slide image directly to Cloudinary and persists secure HTTPS URL.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'image' => 'nullable',
            'file' => 'nullable|file|image|max:10240',
            'subtitle' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $uploadedFile = $request->file('image') ?? $request->file('file');
            $imageInput = $uploadedFile ?? $request->input('image');

            $imageUrl = null;
            if (!empty($imageInput)) {
                $imageUrl = CloudinaryService::upload($imageInput, 'rasha_portfolio/slides');
            }
            if (!$imageUrl) {
                if ($uploadedFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload slide image to Cloudinary.');
                }
                $imageUrl = is_string($imageInput) ? $imageInput : '/home-imgs/home1.webp';
            }

            $slide = HomeSlide::create([
                'image' => $imageUrl,
                'subtitle' => $validated['subtitle'],
                'title' => $validated['title'],
                'description' => $validated['description'],
                'sort_order' => $validated['sort_order'] ?? 0,
            ]);

            return response()->json($slide, 201);
        } catch (\Throwable $e) {
            Log::error('HomeSlideController store Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update an existing hero slide (Admin CMS).
     * Uploads updated image directly to Cloudinary if provided.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $slide = HomeSlide::findOrFail($id);

        $validated = $request->validate([
            'image' => 'nullable',
            'file' => 'nullable|file|image|max:10240',
            'subtitle' => 'sometimes|required|string|max:255',
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'sort_order' => 'nullable|integer',
        ]);

        try {
            $uploadedFile = $request->file('image') ?? $request->file('file');
            $imageInput = $uploadedFile ?? $request->input('image');

            $imageUrl = $slide->image;
            if (!empty($imageInput)) {
                $uploadedUrl = CloudinaryService::upload($imageInput, 'rasha_portfolio/slides');
                if ($uploadedUrl) {
                    $imageUrl = $uploadedUrl;
                } elseif ($uploadedFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload updated slide image to Cloudinary.');
                }
            }

            $slide->update([
                'image' => $imageUrl,
                'subtitle' => $validated['subtitle'] ?? $slide->subtitle,
                'title' => $validated['title'] ?? $slide->title,
                'description' => $validated['description'] ?? $slide->description,
                'sort_order' => $validated['sort_order'] ?? $slide->sort_order,
            ]);

            return response()->json($slide);
        } catch (\Throwable $e) {
            Log::error('HomeSlideController update Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a hero slide.
     */
    public function destroy(int $id): JsonResponse
    {
        $slide = HomeSlide::findOrFail($id);
        $slide->delete();

        return response()->json(['message' => 'Slide deleted successfully.']);
    }
}
