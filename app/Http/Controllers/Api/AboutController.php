<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutContent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\CloudinaryService;

class AboutController extends Controller
{
    /**
     * Get the current About page content.
     */
    public function show(): JsonResponse
    {
        $about = AboutContent::first();

        if (!$about) {
            return response()->json([
                'main_image' => '/home-imgs/project-14-1.webp',
                'main_title' => 'Crafting Sanctuaries of Grace, Form & Substance',
                'main_description' => 'Founded by visionary architect Rasha Wassouf, our studio is dedicated to delivering bespoke architectural and interior design environments that redefine modern luxury. We approach every commission as a unique canvas—harmonizing the natural context, functional elegance, and structural innovation.',
                'secondary_description' => 'From private residential villas to prestigious commercial headquarters, our philosophy is anchored in timeless aesthetics over transient trends. Every line drawn is purposeful; every texture selected is enduring.',
                'quote_text' => '"Architecture is frozen music, illuminated by living light."',
                'quote_author' => '— RASHA WASSOUF',
                'badge_number' => '3+',
                'badge_label' => 'YEARS OF EXCELLENCE',
                'statistics' => [
                    ['id' => 1, 'value' => 3, 'symbol' => '+', 'label' => 'Years of Architectural Mastery'],
                    ['id' => 2, 'value' => 120, 'symbol' => '+', 'label' => 'Bespoke Projects Realized'],
                    ['id' => 3, 'value' => 18, 'symbol' => '', 'label' => 'Prestigious Design Awards'],
                    ['id' => 4, 'value' => 100, 'symbol' => '%', 'label' => 'Dedicated Client Care'],
                ],
                'pillars' => [
                    ['id' => 1, 'icon' => 'architecture', 'title' => 'Architectural Pureness', 'description' => 'Sculptural silhouettes and structural precision calculated to create breathtaking emotional impact.'],
                    ['id' => 2, 'icon' => 'diamond', 'title' => 'Bespoke Materiality', 'description' => 'Curating rare stones, hand-finished metals, and tactile timbers sourced from world-class artisans.'],
                    ['id' => 3, 'icon' => 'auto_awesome', 'title' => 'Light & Spatial Flow', 'description' => 'Choreographing natural daylight and shadow to accentuate depth, tranquility, and spatial grandeur.'],
                    ['id' => 4, 'icon' => 'workspace_premium', 'title' => 'End-to-End Execution', 'description' => 'Seamlessly directing every phase from master planning and 3D visualization to on-site turn-key realization.'],
                ],
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return response()->json($about)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Update the About page content (Admin CMS).
     */
    public function update(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'image' => 'nullable',
                'main_image' => 'nullable',
                'file' => 'nullable',
                'photo' => 'nullable',
                'main_title' => 'required|string|max:255',
                'main_description' => 'required|string',
                'secondary_description' => 'nullable|string',
                'quote_text' => 'nullable|string',
                'quote_author' => 'nullable|string|max:255',
                'badge_number' => 'nullable|string|max:50',
                'badge_label' => 'nullable|string|max:100',
                'statistics' => 'nullable',
                'pillars' => 'nullable',
            ]);

            // 1. Process image upload directly to Cloudinary (Zero local disk usage)
            $uploadedFile = $request->file('image') ?? $request->file('main_image') ?? $request->file('file') ?? $request->file('photo');
            $imageInput = $uploadedFile 
                ?? $request->input('main_image') 
                ?? $request->input('image') 
                ?? $request->input('file') 
                ?? $request->input('photo');

            if (!empty($imageInput)) {
                $cloudinaryUrl = CloudinaryService::upload($imageInput, 'rasha_portfolio/about');
                if ($cloudinaryUrl) {
                    $validated['main_image'] = $cloudinaryUrl;
                } else {
                    if ($uploadedFile instanceof UploadedFile || (is_string($imageInput) && str_starts_with($imageInput, 'data:image/'))) {
                        throw new \RuntimeException('Failed to upload About page image to Cloudinary.');
                    }
                }
            }

            // Remove temporary 'image' attribute before updating model
            unset($validated['image']);

            // 3. Handle statistics array or JSON string from FormData
            if (isset($validated['statistics']) && is_string($validated['statistics'])) {
                $decoded = json_decode($validated['statistics'], true);
                if (is_array($decoded)) {
                    $validated['statistics'] = $decoded;
                }
            }

            // 4. Handle pillars array or JSON string from FormData
            if (isset($validated['pillars']) && is_string($validated['pillars'])) {
                $decoded = json_decode($validated['pillars'], true);
                if (is_array($decoded)) {
                    $validated['pillars'] = $decoded;
                }
            }

            $about = AboutContent::first();

            // If main_image is not provided or empty string, preserve existing image
            if (empty($validated['main_image']) && $about && !empty($about->main_image)) {
                $validated['main_image'] = $about->main_image;
            }

            if ($about) {
                $about->update($validated);
            } else {
                $about = AboutContent::create($validated);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'About page content updated successfully.',
                'data' => $about->fresh(),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        } catch (\Throwable $e) {
            Log::error('AboutController update error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
