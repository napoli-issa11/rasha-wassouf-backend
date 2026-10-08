<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a newly created comment from a visitor.
     * Default status is strictly 'pending' and hidden from public visitors.
     */
    public function store(Request $request, int $projectId): JsonResponse
    {
        $project = Project::findOrFail($projectId);

        $validated = $request->validate([
            'author' => 'required|string|max:100',
            'text' => 'required|string|max:1000',
            'avatarColor' => 'nullable|string',
        ]);

        $comment = $project->comments()->create([
            'author' => trim($validated['author']),
            'text' => trim($validated['text']),
            'status' => 'pending', // Pending moderation by default
            'avatar_color' => $validated['avatarColor'] ?? '#D4AF37',
        ]);

        return response()->json([
            'message' => 'Your comment has been submitted and is awaiting administrative approval.',
            'comment' => [
                'id' => (string) $comment->id,
                'projectId' => $comment->project_id,
                'author' => $comment->author,
                'text' => $comment->text,
                'status' => $comment->status,
                'createdAt' => $comment->created_at->toIso8601String(),
                'avatarColor' => $comment->avatar_color,
            ],
        ], 201);
    }

    /**
     * Admin: Fetch all pending comments across all projects for moderation.
     */
    public function pending(): JsonResponse
    {
        $comments = Comment::with('project')
            ->pending()
            ->latest()
            ->get()
            ->map(function ($comment) {
                return [
                    'id' => (string) $comment->id,
                    'projectId' => $comment->project_id,
                    'projectTitle' => $comment->project->title ?? 'Unknown Project',
                    'author' => $comment->author,
                    'text' => $comment->text,
                    'status' => $comment->status,
                    'createdAt' => $comment->created_at->toIso8601String(),
                    'avatarColor' => $comment->avatar_color,
                ];
            });

        return response()->json($comments);
    }

    /**
     * Admin: Approve a comment (status changes to 'published').
     * The comment will immediately appear publicly.
     */
    public function approve(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->update(['status' => 'published']);

        return response()->json([
            'message' => 'Comment approved and published to website.',
            'comment' => $comment,
        ]);
    }

    /**
     * Admin: Delete/Reject a comment permanently.
     */
    public function destroy(int $id): JsonResponse
    {
        $comment = Comment::findOrFail($id);
        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully.']);
    }
}
