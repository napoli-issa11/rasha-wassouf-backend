<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Get global settings (social media URLs).
     * Accessible by public frontend (Footer) and Admin CMS.
     */
    public function index(): JsonResponse
    {
        $settings = Setting::first();

        if (!$settings) {
            return response()->json([
                'facebook_url' => 'https://facebook.com/rashawassouf.arch',
                'instagram_url' => 'https://instagram.com/rashawassouf',
                'linkedin_url' => 'https://linkedin.com/in/rashawassouf',
                'threads_url' => 'https://threads.net/@rashawassouf',
                'pinterest_url' => 'https://pinterest.com/rashawassouf',
                'show_facebook' => true,
                'show_instagram' => true,
                'show_linkedin' => true,
                'show_threads' => true,
                'show_pinterest' => true,
                'inquiry_settings' => null,
                'hero_rotation_speed' => 3,
            ]);
        }

        // Ensure default 3s if not set
        if (!isset($settings->hero_rotation_speed) || $settings->hero_rotation_speed < 1) {
            $settings->hero_rotation_speed = 3;
        }

        // Ensure boolean defaults
        $settings->show_facebook = $settings->show_facebook ?? true;
        $settings->show_instagram = $settings->show_instagram ?? true;
        $settings->show_linkedin = $settings->show_linkedin ?? true;
        $settings->show_threads = $settings->show_threads ?? true;
        $settings->show_pinterest = $settings->show_pinterest ?? true;

        return response()->json($settings);
    }

    /**
     * Update global settings (social media URLs, visibility toggles & inquiry strings).
     * Protected for Admin CMS (auth:sanctum).
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'facebook_url' => 'nullable|string|max:255',
            'instagram_url' => 'nullable|string|max:255',
            'linkedin_url' => 'nullable|string|max:255',
            'threads_url' => 'nullable|string|max:255',
            'pinterest_url' => 'nullable|string|max:255',
            'show_facebook' => 'nullable|boolean',
            'show_instagram' => 'nullable|boolean',
            'show_linkedin' => 'nullable|boolean',
            'show_threads' => 'nullable|boolean',
            'show_pinterest' => 'nullable|boolean',
            'inquiry_settings' => 'nullable|array',
            'hero_rotation_speed' => 'nullable|integer|min:1|max:60',
        ]);

        $settings = Setting::first();

        if ($settings) {
            $settings->update($validated);
        } else {
            $settings = Setting::create($validated);
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'data' => $settings,
        ]);
    }
}
