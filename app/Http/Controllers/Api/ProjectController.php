<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Comment;
use App\Services\CloudinaryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProjectController extends Controller
{
    /**
     * Display a listing of projects.
     * Public visitor sees ONLY published comments. Initial likes & comments default to 0.
     */
    public function index(): JsonResponse
    {
        $projects = Project::with(['publishedComments' => function ($query) {
            $query->orderBy('created_at', 'desc');
        }])->latest()->get()->map(function ($project) {
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

        return response()->json($projects);
    }

    /**
     * Store a newly created project (Admin CMS).
     * Uploads images directly to Cloudinary and persists secure HTTPS URLs.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'description' => 'required|string',
            'coverImage' => 'nullable',
            'cover_image' => 'nullable',
            'image' => 'nullable',
            'images' => 'nullable',
            'folderName' => 'nullable|string|max:255',
            'folder_name' => 'nullable|string|max:255',
        ]);

        try {
            // 1. Resolve Cover Image (Uploaded File, Base64 data URI, or URL string)
            $coverFile = $request->file('coverImage') 
                ?? $request->file('cover_image') 
                ?? $request->file('image')
                ?? $request->file('file')
                ?? $request->file('photo');

            $coverInput = $coverFile 
                ?? $request->input('coverImage') 
                ?? $request->input('cover_image') 
                ?? $request->input('image')
                ?? $request->input('file')
                ?? $request->input('photo');

            $coverImageUrl = null;
            if (!empty($coverInput)) {
                $coverImageUrl = CloudinaryService::upload($coverInput, 'rasha_portfolio/projects');
            }
            if (!$coverImageUrl) {
                if ($coverFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload project cover image to Cloudinary.');
                }
                $coverImageUrl = (is_string($coverInput) && !str_starts_with($coverInput, 'blob:')) ? $coverInput : '/project 01/project-1-1.webp';
            }

            // 2. Resolve Gallery Images (Array of Files, Base64s, or URLs)
            $galleryImages = [];
            $uploadedImages = $request->file('images');
            $inputImages = $request->input('images');

            if (is_string($inputImages)) {
                $decoded = json_decode($inputImages, true);
                if (is_array($decoded)) {
                    $inputImages = $decoded;
                }
            }

            if (is_array($uploadedImages)) {
                foreach ($uploadedImages as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $url = CloudinaryService::upload($file, 'rasha_portfolio/projects');
                        if ($url) $galleryImages[] = $url;
                    }
                }
            } elseif (is_array($inputImages)) {
                foreach ($inputImages as $img) {
                    if (is_string($img) && str_starts_with($img, 'blob:')) {
                        continue;
                    }
                    $url = CloudinaryService::upload($img, 'rasha_portfolio/projects') ?? (is_string($img) ? $img : null);
                    if ($url) $galleryImages[] = $url;
                }
            }

            // Check if coverIndex or cover_index was passed
            $coverIndex = $request->input('coverIndex') ?? $request->input('cover_index');
            if ($coverIndex !== null && isset($galleryImages[(int) $coverIndex])) {
                $coverImageUrl = $galleryImages[(int) $coverIndex];
            } elseif ((empty($coverImageUrl) || $coverImageUrl === '/project 01/project-1-1.webp') && !empty($galleryImages)) {
                $coverImageUrl = $galleryImages[0];
            }

            if (empty($galleryImages)) {
                $galleryImages = [$coverImageUrl ?: '/project 01/project-1-1.webp'];
            }

            // Ensure cover image is included in gallery images
            if ($coverImageUrl && !in_array($coverImageUrl, $galleryImages)) {
                array_unshift($galleryImages, $coverImageUrl);
            }

            $folderName = $this->generateFolderName(
                $request->input('folderName') ?? $request->input('folder_name')
            );

            $project = Project::create([
                'folder_name' => $folderName,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'description' => $validated['description'],
                'cover_image' => $coverImageUrl ?: ($galleryImages[0] ?? '/project 01/project-1-1.webp'),
                'images' => array_values(array_unique($galleryImages)),
                'likes' => 0,
            ]);

            return response()->json([
                'id' => $project->id,
                'folderName' => $project->folder_name,
                'title' => $project->title,
                'category' => $project->category,
                'description' => $project->description,
                'coverImage' => $project->cover_image,
                'images' => $project->images,
                'likes' => (int) $project->likes,
                'comments' => [],
                'created_at' => $project->created_at->toIso8601String(),
            ], 201);
        } catch (\Throwable $e) {
            Log::error('ProjectController store Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified project.
     */
    public function show(int $id): JsonResponse
    {
        $project = Project::with('publishedComments')->findOrFail($id);
        return response()->json($project);
    }

    /**
     * Update the specified project in database.
     * Uploads any new images directly to Cloudinary and persists secure HTTPS URLs.
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
            'images' => 'nullable',
            'folderName' => 'nullable|string',
            'folder_name' => 'nullable|string',
        ]);

        try {
            // 1. Check for newly uploaded cover image
            $coverFile = $request->file('coverImage') 
                ?? $request->file('cover_image') 
                ?? $request->file('image')
                ?? $request->file('file')
                ?? $request->file('photo');

            $coverInput = $coverFile 
                ?? $request->input('coverImage') 
                ?? $request->input('cover_image') 
                ?? $request->input('image')
                ?? $request->input('file')
                ?? $request->input('photo');

            $coverImageUrl = $project->cover_image;
            if (!empty($coverInput) && !(is_string($coverInput) && str_starts_with($coverInput, 'blob:'))) {
                $uploadedUrl = CloudinaryService::upload($coverInput, 'rasha_portfolio/projects');
                if ($uploadedUrl) {
                    $coverImageUrl = $uploadedUrl;
                } elseif ($coverFile instanceof UploadedFile) {
                    throw new \RuntimeException('Failed to upload updated cover image to Cloudinary.');
                }
            }

            // 2. Check for gallery images
            $galleryImages = $project->images ?? [$coverImageUrl];
            $uploadedImages = $request->file('images');
            $inputImages = $request->input('images');

            if (is_string($inputImages)) {
                $decoded = json_decode($inputImages, true);
                if (is_array($decoded)) {
                    $inputImages = $decoded;
                }
            }

            if (is_array($uploadedImages)) {
                $galleryImages = [];
                foreach ($uploadedImages as $file) {
                    if ($file instanceof UploadedFile && $file->isValid()) {
                        $url = CloudinaryService::upload($file, 'rasha_portfolio/projects');
                        if ($url) $galleryImages[] = $url;
                    }
                }
            } elseif (is_array($inputImages)) {
                $galleryImages = [];
                foreach ($inputImages as $img) {
                    if (is_string($img) && str_starts_with($img, 'blob:')) {
                        continue;
                    }
                    $url = CloudinaryService::upload($img, 'rasha_portfolio/projects') ?? (is_string($img) ? $img : null);
                    if ($url) $galleryImages[] = $url;
                }
            }

            // Check if coverIndex or cover_index was passed
            $coverIndex = $request->input('coverIndex') ?? $request->input('cover_index');
            if ($coverIndex !== null && isset($galleryImages[(int) $coverIndex])) {
                $coverImageUrl = $galleryImages[(int) $coverIndex];
            } elseif (empty($coverImageUrl) && !empty($galleryImages)) {
                $coverImageUrl = $galleryImages[0];
            }

            if (empty($galleryImages)) {
                $galleryImages = [$coverImageUrl ?: $project->cover_image];
            }

            // Ensure cover image is included in gallery images
            if ($coverImageUrl && !in_array($coverImageUrl, $galleryImages)) {
                array_unshift($galleryImages, $coverImageUrl);
            }

            $incomingFolder = $request->input('folderName') ?? $request->input('folder_name');
            $folderName = $project->folder_name;
            if ($incomingFolder !== null) {
                $trimmed = trim(strip_tags((string) $incomingFolder));
                if ($trimmed !== '') {
                    $folderName = $trimmed;
                }
            }
            if (empty($folderName)) {
                $folderName = $this->generateFolderName(null);
            }

            $project->update([
                'title' => $validated['title'] ?? $project->title,
                'category' => $validated['category'] ?? $project->category,
                'description' => $validated['description'] ?? $project->description,
                'folder_name' => $folderName,
                'cover_image' => $coverImageUrl ?: $project->cover_image,
                'images' => array_values(array_unique($galleryImages)),
            ]);

            return response()->json([
                'id' => $project->id,
                'folderName' => $project->folder_name,
                'title' => $project->title,
                'category' => $project->category,
                'description' => $project->description,
                'coverImage' => $project->cover_image,
                'images' => $project->images,
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
            ]);
        } catch (\Throwable $e) {
            Log::error('ProjectController update Cloudinary upload failed: ' . $e->getMessage(), [
                'exception' => $e,
            ]);
            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified project from database.
     */
    public function destroy(int $id): JsonResponse
    {
        $project = Project::findOrFail($id);
        $project->delete();

        return response()->json(['message' => 'Project deleted successfully']);
    }

    /**
     * Increment Like counter:
     * Ensure the logic only increments the likes_count column in the database ($project->increment('likes_count')).
     * Action is strictly one-way (users can like, but cannot unlike).
     */
    public function toggleLike(int $id, Request $request): JsonResponse
    {
        $project = Project::findOrFail($id);

        if (Schema::hasColumn('projects', 'likes_count')) {
            $project->increment('likes_count');
            $likes = (int) $project->fresh()->likes_count;
        } else {
            $project->increment('likes');
            $likes = (int) $project->fresh()->likes;
        }

        return response()->json([
            'id' => $project->id,
            'likes' => $likes,
            'likes_count' => $likes,
            'isLiked' => true,
        ]);
    }

    /**
     * Dynamic Dashboard Stats endpoint:
     * Counts actual rows from the database tables in real-time.
     */
    public function dashboardStats(): JsonResponse
    {
        $totalProjects = Project::count();
        $totalLikes = (int) Project::sum('likes');
        $pendingComments = Comment::where('status', 'pending')->count();
        $publishedComments = Comment::where('status', 'published')->count();
        $totalComments = Comment::count();

        return response()->json([
            'totalProjects' => $totalProjects,
            'totalLikes' => $totalLikes,
            'pendingComments' => $pendingComments,
            'publishedComments' => $publishedComments,
            'totalComments' => $totalComments,
        ]);
    }

    /**
     * Dedicated image upload for Projects & Categories section.
     * Uploads directly to Cloudinary and returns secure HTTPS URL.
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

        $folder = $request->input('folder', 'rasha_portfolio/projects');

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
                'message' => 'Image uploaded to Cloudinary successfully.',
                'url' => $secureUrl,
                'secure_url' => $secureUrl,
            ], 200);
        } catch (\Throwable $e) {
            Log::error('ProjectController uploadImage Cloudinary error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Cloudinary upload failed: ' . $e->getMessage(),
            ], 500);
        }
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
            // Find max existing number in projects (checking both 'project-XX' and 'project XX')
            $maxNum = 0;
            $allFolders = Project::pluck('folder_name')->all();
            foreach ($allFolders as $f) {
                if ($f && preg_match('/project\D*(\d+)/i', $f, $matches)) {
                    $num = (int) $matches[1];
                    if ($num > $maxNum) {
                        $maxNum = $num;
                    }
                }
            }

            $nextNumber = max($maxNum + 1, count($allFolders) + 1);
            $folderName = "project-{$nextNumber}";

            // Ensure uniqueness across all existing records
            while (Project::where('folder_name', $folderName)->exists() || Project::where('folder_name', "project " . $nextNumber)->exists() || Project::where('folder_name', "project " . sprintf('%02d', $nextNumber))->exists()) {
                $nextNumber++;
                $folderName = "project-{$nextNumber}";
            }
        }

        return $folderName;
    }
}
