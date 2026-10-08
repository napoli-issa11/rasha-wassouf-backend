<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    /**
     * Predefined architectural categories for the portfolio.
     */
    protected const DEFAULT_CATEGORIES = [
        'Architecture',
        'Interior Design',
        'Residential',
        'Commercial',
    ];

    /**
     * Display a listing of categories and their associated projects.
     */
    public function index(Request $request): JsonResponse
    {
        $categoryFilter = $request->query('category');

        $query = Project::with(['publishedComments' => function ($q) {
            $q->orderBy('created_at', 'desc');
        }])->latest();

        if (!empty($categoryFilter) && strtolower($categoryFilter) !== 'all') {
            $query->where('category', $categoryFilter);
        }

        $projects = $query->get()->map(function ($project) {
            return [
                'id' => $project->id,
                'folderName' => $project->folder_name ?? "project {$project->id}",
                'title' => $project->title,
                'category' => $project->category,
                'description' => $project->description,
                'coverImage' => $project->cover_image,
                'images' => $project->images ?? [$project->cover_image],
                'likes' => (int) $project->likes,
                'comments' => $project->publishedComments->map(function ($comment) {
                    return [
                        'id' => (string) $comment->id,
                        'projectId' => $comment->project_id,
                        'author' => $comment->author,
                        'text' => $comment->text,
                        'status' => $comment->status,
                        'createdAt' => $comment->created_at->toIso8601String(),
                        'avatarColor' => $comment->avatar_color,
                    ];
                }),
                'created_at' => $project->created_at->toIso8601String(),
            ];
        });

        // Calculate summary counts per category
        $categoriesSummary = collect(self::DEFAULT_CATEGORIES)->map(function ($cat) {
            $count = Project::where('category', $cat)->count();
            $latest = Project::where('category', $cat)->latest()->first();
            return [
                'name' => $cat,
                'projectCount' => $count,
                'coverImage' => $latest ? $latest->cover_image : null,
            ];
        });

        return response()->json([
            'categories' => $categoriesSummary,
            'projects' => $projects,
        ]);
    }

    /**
     * Dedicated image upload endpoint for Categories section.
     * Every uploaded image routes strictly through Cloudinary (Zero local disk usage).
     * Bypasses local SSL verification and returns clean JSON error on failure.
     */
    public function uploadImage(Request $request): JsonResponse
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

        $folder = $request->input('folder', 'rasha_portfolio/categories');

        try {
            // Routes strictly through Cloudinary with local SSL workaround (verify => false)
            $secureUrl = CloudinaryService::upload($file, $folder);

            if (!$secureUrl) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cloudinary upload failed: No secure URL returned from Cloudinary API.',
                ], 500);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded to Cloudinary successfully.',
                'url' => $secureUrl,
                'secure_url' => $secureUrl,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('[CategoryController] Cloudinary uploadImage error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created category project in database.
     * All image uploads are strictly routed through Cloudinary CDN.
     */
    public function store(Request $request): JsonResponse
    {
        Log::info('Incoming Request Data:', $request->all());
        Log::info('Incoming Files:', $request->allFiles());
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'coverImage' => 'nullable',
            'cover_image' => 'nullable',
            'image' => 'nullable',
            'file' => 'nullable',
            'images' => 'nullable',
            'folderName' => 'nullable|string|max:255',
            'folder_name' => 'nullable|string|max:255',
        ]);

        try {
            // 1. Resolve Cover Image and upload directly to Cloudinary
            $coverFile = $request->file('coverImage')
                ?? $request->file('cover_image')
                ?? $request->file('image')
                ?? $request->file('file');

            $coverInput = $coverFile
                ?? $request->input('coverImage')
                ?? $request->input('cover_image')
                ?? $request->input('image')
                ?? $request->input('file');

            $coverImageUrl = null;
            if (!empty($coverInput)) {
                $coverImageUrl = CloudinaryService::upload($coverInput, 'rasha_portfolio/categories');
            }

            if (!$coverImageUrl) {
                // If a file was explicitly submitted but failed to return URL
                if ($coverFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload category cover image to Cloudinary.');
                }
                $coverImageUrl = is_string($coverInput) ? $coverInput : '/project 01/project-1-1.webp';
            }

            // 2. Resolve Gallery Images (upload each to Cloudinary)
            $galleryImages = [];
            $uploadedImages = $request->file('images');
            $inputImages = $request->input('images');

            if (is_array($uploadedImages)) {
                foreach ($uploadedImages as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $url = CloudinaryService::upload($file, 'rasha_portfolio/categories');
                        if ($url) $galleryImages[] = $url;
                    }
                }
            } elseif (is_array($inputImages)) {
                foreach ($inputImages as $img) {
                    $url = CloudinaryService::upload($img, 'rasha_portfolio/categories') ?? (is_string($img) ? $img : null);
                    if ($url) $galleryImages[] = $url;
                }
            }

            if (empty($galleryImages)) {
                $galleryImages = [$coverImageUrl];
            }

            $folderName = $this->generateFolderName(
                $request->input('folderName') ?? $request->input('folder_name')
            );

            $project = Project::create([
                'folder_name' => $folderName,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'cover_image' => $coverImageUrl,
                'images' => $galleryImages,
                'likes' => 0,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Category project created and images uploaded to Cloudinary.',
                'data' => [
                    'id' => $project->id,
                    'folderName' => $project->folder_name,
                    'title' => $project->title,
                    'category' => $project->category,
                    'description' => $project->description,
                    'coverImage' => $project->cover_image,
                    'images' => $project->images,
                    'likes' => 0,
                    'created_at' => $project->created_at->toIso8601String(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('[CategoryController] store Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified category project.
     */
    public function show(int $id): JsonResponse
    {
        $project = Project::with('publishedComments')->findOrFail($id);
        return response()->json($project);
    }

    /**
     * Update an existing category project.
     * Any updated images route strictly through Cloudinary.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $project = Project::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'coverImage' => 'nullable',
            'cover_image' => 'nullable',
            'image' => 'nullable',
            'file' => 'nullable',
            'images' => 'nullable',
            'folderName' => 'nullable|string',
            'folder_name' => 'nullable|string',
        ]);

        try {
            // 1. Process cover image upload through Cloudinary
            $coverFile = $request->file('coverImage')
                ?? $request->file('cover_image')
                ?? $request->file('image')
                ?? $request->file('file');

            $coverInput = $coverFile
                ?? $request->input('coverImage')
                ?? $request->input('cover_image')
                ?? $request->input('image')
                ?? $request->input('file');

            $coverImageUrl = $project->cover_image;
            if (!empty($coverInput)) {
                $uploadedUrl = CloudinaryService::upload($coverInput, 'rasha_portfolio/categories');
                if ($uploadedUrl) {
                    $coverImageUrl = $uploadedUrl;
                } elseif ($coverFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload updated cover image to Cloudinary.');
                }
            }

            // 2. Process gallery images through Cloudinary
            $galleryImages = $project->images ?? [$coverImageUrl];
            $uploadedImages = $request->file('images');
            $inputImages = $request->input('images');

            if (is_array($uploadedImages)) {
                $galleryImages = [];
                foreach ($uploadedImages as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $url = CloudinaryService::upload($file, 'rasha_portfolio/categories');
                        if ($url) $galleryImages[] = $url;
                    }
                }
            } elseif (is_array($inputImages)) {
                $galleryImages = [];
                foreach ($inputImages as $img) {
                    $url = CloudinaryService::upload($img, 'rasha_portfolio/categories') ?? (is_string($img) ? $img : null);
                    if ($url) $galleryImages[] = $url;
                }
            }

            $project->update([
                'title' => $validated['title'] ?? $project->title,
                'category' => $validated['category'] ?? $project->category,
                'description' => $validated['description'] ?? $project->description,
                'folder_name' => $request->input('folderName') ?? $request->input('folder_name') ?? $project->folder_name,
                'cover_image' => $coverImageUrl,
                'images' => $galleryImages,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Category project updated successfully with Cloudinary media.',
                'data' => [
                    'id' => $project->id,
                    'folderName' => $project->folder_name,
                    'title' => $project->title,
                    'category' => $project->category,
                    'description' => $project->description,
                    'coverImage' => $project->cover_image,
                    'images' => $project->images,
                    'likes' => (int) $project->likes,
                    'created_at' => $project->created_at->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[CategoryController] update Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified category project from database.
     */
    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Category project deleted successfully.',
        ]);
    }

    /**
     * Generate a clean, sequential project folder name (e.g., 'project-15', 'project-16')
     * based on total project count in the database, or sanitize and validate provided input.
     */
    protected function generateFolderName(?string $folderInput): string
    {
        $folderName = is_string($folderInput) ? trim(strip_tags($folderInput)) : '';

        // If omitted, empty, or matches legacy random timestamp pattern, auto-generate sequential identifier
        if ($folderName === '' || preg_match('/^(custom|category)-project-\d+$/', $folderName)) {
            $nextNumber = Project::count() + 1;
            $folderName = "project-{$nextNumber}";

            // Ensure uniqueness across all existing records
            while (Project::where('folder_name', $folderName)->exists()) {
                $nextNumber++;
                $folderName = "project-{$nextNumber}";
            }
        }

        return $folderName;
    }
}
