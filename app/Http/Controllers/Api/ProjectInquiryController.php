<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectInquiryRequest;
use App\Mail\AdminInquiryNotification;
use App\Mail\UserAutoReply;
use App\Mail\ReplyToClientMail;
use App\Models\ProjectInquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProjectInquiryController extends Controller
{
    /**
     * Store a newly created project inquiry from the public form.
     * Validates, saves to database, and asynchronously queues two emails.
     */
    public function store(StoreProjectInquiryRequest $request): JsonResponse
    {
        // 1. Retrieve sanitized & validated input
        $validated = $request->validated();

        // 2. Attach security audit telemetry
        $validated['ip_address'] = $request->ip();
        $validated['user_agent'] = substr((string) $request->userAgent(), 0, 500);
        $validated['status'] = 'new';

        // 3. Persist securely via Eloquent ORM (Parameterized SQL Injection protection)
        $inquiry = ProjectInquiry::create($validated);

        // 4. Asynchronously dispatch emails via queue (ShouldQueue prevents blocking HTTP response)
        try {
            // Email 1: Admin Notification - destination strictly configured from environment
            $adminEmail = config('mail.admin_address') 
                ?: env('ADMIN_EMAIL') 
                ?: env('MAIL_FROM_ADDRESS');

            if ($adminEmail) {
                Mail::to($adminEmail)->queue(new AdminInquiryNotification($inquiry));
            } else {
                Log::warning('ADMIN_EMAIL not configured in .env. Skipping admin inquiry notification email dispatch.');
            }

            // Email 2: Professional Auto-Reply to Client
            Mail::to($inquiry->email)->queue(new UserAutoReply($inquiry));
        } catch (\Throwable $e) {
            // Log queue/mail failure without breaking the client HTTP success response
            Log::error('Failed to queue project inquiry emails: ' . $e->getMessage(), [
                'inquiry_id' => $inquiry->id,
                'exception' => $e,
            ]);
        }

        // 5. Return clean structured JSON response
        return response()->json([
            'status' => 'success',
            'message' => 'Thank you. Your project brief has been securely transmitted. Our studio will review your vision and contact you within 24 hours.',
            'data' => [
                'id' => $inquiry->id,
                'full_name' => $inquiry->full_name,
                'email' => $inquiry->email,
                'project_category' => $inquiry->project_category,
                'created_at' => $inquiry->created_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Admin Dashboard: Retrieve all inquiries securely (Protected).
     */
    public function index(Request $request): JsonResponse
    {
        $query = ProjectInquiry::query()->recent();

        // Optional status filter (e.g. 'new', 'contacted', 'archived')
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Optional search term
        if ($request->filled('search')) {
            $search = '%' . $request->search . '%';
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', $search)
                  ->orWhere('email', 'like', $search)
                  ->orWhere('telephone', 'like', $search)
                  ->orWhere('project_category', 'like', $search);
            });
        }

        $inquiries = $query->paginate(25);

        return response()->json($inquiries);
    }

    /**
     * Admin Dashboard: Retrieve single inquiry detail (Protected).
     */
    public function show(int $id): JsonResponse
    {
        $inquiry = ProjectInquiry::findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $inquiry,
        ]);
    }

    /**
     * Admin Dashboard: Update inquiry status (Protected).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:new,in_review,contacted,archived',
        ]);

        $inquiry = ProjectInquiry::findOrFail($id);
        $inquiry->update(['status' => $request->status]);

        return response()->json([
            'status' => 'success',
            'message' => 'Inquiry status updated successfully.',
            'data' => $inquiry,
        ]);
    }

    /**
     * Admin Dashboard: Delete an inquiry (Protected).
     */
    public function destroy(int $id): JsonResponse
    {
        $inquiry = ProjectInquiry::findOrFail($id);
        $inquiry->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Project inquiry removed successfully.',
        ]);
    }

    /**
     * Admin Dashboard: Send a direct email reply to a client inquiry (Protected).
     * Sends email immediately via Mail::to($request->email)->send(...) instead of relying on frontend mailto.
     */
    public function reply(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'inquiry_id' => 'nullable|integer|exists:project_inquiries,id',
        ]);

        $recipientEmail = trim($validated['email']);
        $messageText = trim($validated['message']);
        $subject = !empty($validated['subject']) ? trim($validated['subject']) : 'Regarding Your Project Inquiry - Rasha Wassouf Design Studio';
        $clientName = !empty($validated['name']) ? trim($validated['name']) : 'Valued Client';
        $projectCategory = null;

        // If an inquiry ID was provided, enrich email context and update status to contacted
        if (!empty($validated['inquiry_id'])) {
            $inquiry = ProjectInquiry::find($validated['inquiry_id']);
            if ($inquiry) {
                if (empty($validated['name']) && !empty($inquiry->full_name)) {
                    $clientName = $inquiry->full_name;
                }
                $projectCategory = $inquiry->project_category;
                $inquiry->update(['status' => 'contacted']);
            }
        }

        try {
            Mail::to($recipientEmail)->send(new ReplyToClientMail(
                clientName: $clientName,
                emailSubject: $subject,
                replyMessage: $messageText,
                projectCategory: $projectCategory
            ));
        } catch (\Throwable $e) {
            Log::error('Failed to send reply email to client: ' . $e->getMessage(), [
                'recipient' => $recipientEmail,
                'inquiry_id' => $validated['inquiry_id'] ?? null,
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send email: ' . $e->getMessage(),
            ], 500);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Reply email sent successfully to ' . $recipientEmail,
        ]);
    }
}
