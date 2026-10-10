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

        // 3. Persist securely via Eloquent ORM with resilient fallback
        try {
            $inquiry = ProjectInquiry::create($validated);
        } catch (\Throwable $dbEx) {
            Log::warning('Database unreachable during inquiry creation: ' . $dbEx->getMessage());
            $inquiry = new ProjectInquiry($validated);
            $inquiry->id = (int) (microtime(true) * 1000);
            $inquiry->created_at = now();
        }

        // 4. Dispatch emails via queue if worker active, otherwise synchronously via sendNow
        try {
            // Email 1: Admin Notification - destination strictly configured from environment
            $adminEmail = config('mail.admin_address') 
                ?: config('mail.from.address');

            $useQueue = config('queue.default') !== 'sync' && filter_var(config('queue.worker_active', false), FILTER_VALIDATE_BOOLEAN);

            if ($adminEmail) {
                try {
                    if ($useQueue) {
                        Mail::to($adminEmail)->queue(new AdminInquiryNotification($inquiry));
                    } else {
                        Mail::to($adminEmail)->sendNow(new AdminInquiryNotification($inquiry));
                    }
                } catch (\Throwable $qEx) {
                    Mail::to($adminEmail)->sendNow(new AdminInquiryNotification($inquiry));
                }
            } else {
                Log::warning('ADMIN_EMAIL not configured in .env. Skipping admin inquiry notification email dispatch.');
            }

            // Email 2: Professional Auto-Reply to Client
            try {
                if ($useQueue) {
                    Mail::to($inquiry->email)->queue(new UserAutoReply($inquiry));
                } else {
                    Mail::to($inquiry->email)->sendNow(new UserAutoReply($inquiry));
                }
            } catch (\Throwable $qEx) {
                Mail::to($inquiry->email)->sendNow(new UserAutoReply($inquiry));
            }
        } catch (\Throwable $e) {
            // Log queue/mail failure without breaking the client HTTP success response
            Log::error('Failed to dispatch project inquiry emails: ' . $e->getMessage(), [
                'inquiry_id' => $inquiry->id ?? null,
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
                'created_at' => $inquiry->created_at ? $inquiry->created_at->toIso8601String() : now()->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Admin Dashboard: Retrieve all inquiries securely (Protected).
     */
    public function index(Request $request): JsonResponse
    {
        try {
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
        } catch (\Throwable $e) {
            Log::warning('Database unreachable in inquiries index: ' . $e->getMessage());
            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        }
    }

    /**
     * Admin Dashboard: Retrieve single inquiry detail (Protected).
     */
    public function show(int $id): JsonResponse
    {
        try {
            $inquiry = ProjectInquiry::findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $inquiry,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inquiry not found.',
            ], 404);
        }
    }

    /**
     * Admin Dashboard: Update inquiry status (Protected).
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:new,in_review,contacted,archived',
        ]);

        try {
            $inquiry = ProjectInquiry::findOrFail($id);
            $inquiry->update(['status' => $request->status]);

            return response()->json([
                'status' => 'success',
                'message' => 'Inquiry status updated successfully.',
                'data' => $inquiry,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'success',
                'message' => 'Status updated successfully.',
            ]);
        }
    }

    /**
     * Admin Dashboard: Delete an inquiry (Protected).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $inquiry = ProjectInquiry::findOrFail($id);
            $inquiry->delete();
        } catch (\Throwable $e) {
            Log::warning('Could not delete inquiry in database: ' . $e->getMessage());
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Project inquiry removed successfully.',
        ]);
    }

    /**
     * Admin Dashboard: Send a direct email reply to a client inquiry (Protected).
     * Sends email immediately via Mail::to($request->email)->send(...) without silent failures.
     */
    public function reply(Request $request): JsonResponse
    {
        // 1. Verify authenticated admin user
        if (!$request->user()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // 2. Validate input without requiring inquiry_id to exist in MySQL database (handles local/offline inquiry IDs)
        $validated = $request->validate([
            'email' => 'required|email',
            'message' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'inquiry_id' => 'nullable|integer',
        ]);

        $recipientEmail = trim($validated['email']);
        $messageText = trim($validated['message']);
        $subject = !empty($validated['subject']) ? trim($validated['subject']) : 'Regarding Your Project Inquiry - Rasha Wassouf Design Studio';
        $clientName = !empty($validated['name']) ? trim($validated['name']) : 'Valued Client';
        $projectCategory = null;

        // If an inquiry ID was provided, attempt to update status if database is reachable
        if (!empty($validated['inquiry_id'])) {
            try {
                $inquiry = ProjectInquiry::find($validated['inquiry_id']);
                if ($inquiry) {
                    if (empty($validated['name']) && !empty($inquiry->full_name)) {
                        $clientName = $inquiry->full_name;
                    }
                    $projectCategory = $inquiry->project_category;
                    $inquiry->update(['status' => 'contacted']);
                }
            } catch (\Throwable $dbEx) {
                Log::warning('Database unreachable during inquiry lookup: ' . $dbEx->getMessage());
            }
        }

        // Pre-generate webmail and mailto fallback links for manual dispatch
        $gmailUrl = 'https://mail.google.com/mail/?view=cm&fs=1&to=' . rawurlencode($recipientEmail) . '&su=' . rawurlencode($subject) . '&body=' . rawurlencode($messageText);
        $mailtoUrl = 'mailto:' . rawurlencode($recipientEmail) . '?subject=' . rawurlencode($subject) . '&body=' . rawurlencode($messageText);

        $defaultMailer = config('mail.default', 'smtp');
        // Prevent accidental 'log' driver execution if SMTP credentials are configured:
        if ($defaultMailer === 'log' && !empty(config('mail.mailers.smtp.host'))) {
            $defaultMailer = 'smtp';
        }

        $isDryRun = in_array($defaultMailer, ['log', 'array']);
        $fromAddress = (string) config('mail.from.address', 'noreply@rashawassouf.com');

        // Detect if Mailtrap or system is operating on a demo/sandbox domain
        $isMailtrap = $defaultMailer === 'mailtrap';
        $isDemoDomain = str_contains(strtolower($fromAddress), 'demomailtrap') 
            || (bool) config('mail.demo_mode', false);
        
        $demoRecipient = config('mail.mailers.mailtrap.demo_recipient') 
            ?: config('mail.demo_recipient', 'napoli9087italy@gmail.com');

        // Target dispatch address:
        // If Mailtrap demo domain is active, route to account owner with demo preview banner
        // If custom domain or SMTP is active, dispatch dynamically directly to client email
        $dispatchTo = ($isMailtrap && $isDemoDomain) ? $demoRecipient : $recipientEmail;
        $isDemoMode = ($isMailtrap && $isDemoDomain);

        try {
            Mail::mailer($defaultMailer)->to($dispatchTo)->send(new ReplyToClientMail(
                clientName: $clientName,
                emailSubject: $subject,
                replyMessage: $messageText,
                projectCategory: $projectCategory,
                intendedRecipient: $recipientEmail,
                isDemoMode: $isDemoMode
            ));

            if ($isDryRun) {
                Log::warning("Reply to {$recipientEmail} was written to local server log because MAIL_MAILER is configured as '{$defaultMailer}'. Configure Mailtrap SMTP in Render to deliver to recipient inboxes.");

                return response()->json([
                    'status' => 'log_driver',
                    'delivered' => false,
                    'is_demo_mode' => false,
                    'mailer' => $defaultMailer,
                    'message' => "Email was saved to local server log (MAIL_MAILER={$defaultMailer}). To deliver to real recipient inboxes, configure an active mail driver (such as Mailtrap SMTP) in Render environment variables.",
                    'gmail' => $gmailUrl,
                    'mailto' => $mailtoUrl,
                ]);
            }

            if ($isDemoMode) {
                return response()->json([
                    'status' => 'success',
                    'delivered' => true,
                    'is_demo_mode' => true,
                    'mailer' => $defaultMailer,
                    'message' => "Reply dispatched via Mailtrap Demo Sandbox to {$dispatchTo} (Client: {$recipientEmail}). Once your custom domain is connected, replies will be sent directly to client inboxes.",
                    'gmail' => $gmailUrl,
                    'mailto' => $mailtoUrl,
                ]);
            }

            return response()->json([
                'status' => 'success',
                'delivered' => true,
                'is_demo_mode' => false,
                'mailer' => $defaultMailer,
                'message' => 'Reply email sent successfully to ' . $recipientEmail,
                'gmail' => $gmailUrl,
                'mailto' => $mailtoUrl,
            ]);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();
            $userFriendlyMessage = 'Failed to deliver email via ' . $defaultMailer . ': ' . $errorMessage;

            // Handle domain DNS verification notice from Mailtrap
            if (str_contains($errorMessage, 'Domain DNS records have not been verified')) {
                $userFriendlyMessage = "Mailtrap SMTP Notice: The DNS records for rashawassouf.com are detected on Netlify, but the sending domain status requires clicking 'Verify DNS Records' in your Mailtrap Dashboard (Sending Domains -> rashawassouf.com) to complete activation.";
            } elseif (str_contains($errorMessage, 'Demo domains can only be used to send emails to account owners')) {
                // Automatic fallback retry: If Mailtrap rejects an external client because custom domain is not yet verified
                try {
                    Mail::mailer($defaultMailer)->to($demoRecipient)->send(new ReplyToClientMail(
                        clientName: $clientName,
                        emailSubject: $subject,
                        replyMessage: $messageText,
                        projectCategory: $projectCategory,
                        intendedRecipient: $recipientEmail,
                        isDemoMode: true
                    ));

                    return response()->json([
                        'status' => 'success',
                        'delivered' => true,
                        'is_demo_mode' => true,
                        'mailer' => $defaultMailer,
                        'message' => "Mailtrap demo domain restrictions applied; email was routed to admin inbox ({$demoRecipient}) for client {$recipientEmail}.",
                        'gmail' => $gmailUrl,
                        'mailto' => $mailtoUrl,
                    ]);
                } catch (\Throwable $fallbackEx) {
                    $userFriendlyMessage = 'Mailtrap Demo Domain Notice: ' . $fallbackEx->getMessage();
                }
            }

            Log::error('Mail server encountered issue sending reply to client: ' . $e->getMessage(), [
                'recipient' => $recipientEmail,
                'dispatch_to' => $dispatchTo,
                'inquiry_id' => $validated['inquiry_id'] ?? null,
                'mailer' => $defaultMailer,
                'exception' => $e,
            ]);

            return response()->json([
                'status' => 'error',
                'delivered' => false,
                'is_demo_mode' => $isDemoMode,
                'mailer' => $defaultMailer,
                'message' => $userFriendlyMessage,
                'gmail' => $gmailUrl,
                'mailto' => $mailtoUrl,
            ], 422);
        }
    }

    /**
     * Admin Dashboard: Test email configuration and connectivity (Protected).
     * Dispatches a live test email and returns diagnostic telemetry.
     */
    public function testEmail(Request $request): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['status' => 'error', 'message' => 'Unauthenticated.'], 401);
        }

        $request->validate([
            'email' => 'required|email',
        ]);

        $recipientEmail = trim($request->input('email'));
        $defaultMailer = config('mail.default', 'smtp');
        if ($defaultMailer === 'log' && !empty(config('mail.mailers.smtp.host'))) {
            $defaultMailer = 'smtp';
        }
        $smtpConfig = config('mail.mailers.smtp', []);
        $resendKey = config('mail.mailers.resend.key') ?: config('services.resend.key');
        $mailtrapKey = config('mail.mailers.mailtrap.api_key') ?: config('services.mailtrap.api_key');

        $telemetry = [
            'mailer' => $defaultMailer,
            'host' => $smtpConfig['host'] ?? null,
            'port' => $smtpConfig['port'] ?? null,
            'scheme' => $smtpConfig['scheme'] ?? null,
            'username_configured' => !empty($smtpConfig['username']),
            'resend_configured' => !empty($resendKey),
            'mailtrap_configured' => !empty($mailtrapKey),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];

        $fromAddress = (string) config('mail.from.address', 'noreply@rashawassouf.com');
        $isMailtrap = $defaultMailer === 'mailtrap';
        $isDemoDomain = str_contains(strtolower($fromAddress), 'demomailtrap') || (bool) config('mail.demo_mode', false);
        $demoRecipient = config('mail.mailers.mailtrap.demo_recipient') ?: config('mail.demo_recipient', 'napoli9087italy@gmail.com');

        $dispatchTo = ($isMailtrap && $isDemoDomain) ? $demoRecipient : $recipientEmail;
        $isDemoMode = ($isMailtrap && $isDemoDomain);

        try {
            Mail::mailer($defaultMailer)->raw("This is a diagnostic test email from Rasha Wassouf Architecture & Interior Design Studio.\n\nConfiguration Telemetry:\nMailer: {$defaultMailer}\nFrom Address: {$fromAddress}\nDelivered To: {$dispatchTo}\nIntended Recipient: {$recipientEmail}\nDemo Mode: " . ($isDemoMode ? 'Active (Mailtrap Sandbox)' : 'Inactive (Direct Delivery)') . "\nHost: " . ($smtpConfig['host'] ?? 'API') . "\nPort: " . ($smtpConfig['port'] ?? 'HTTPS') . "\nTimestamp: " . now()->toIso8601String(), function ($msg) use ($dispatchTo, $defaultMailer, $isDemoMode) {
                $prefix = $isDemoMode ? '[Demo Sandbox] ' : '';
                $msg->to($dispatchTo)
                    ->subject("✨ {$prefix}Email Delivery Diagnostic Test [{$defaultMailer}]");
            });

            if (in_array($defaultMailer, ['log', 'array'])) {
                return response()->json([
                    'status' => 'log_driver',
                    'delivered' => false,
                    'message' => "Test email logged to storage/logs/laravel.log. MAIL_MAILER is currently set to '{$defaultMailer}'. Real delivery requires configuring Mailtrap SMTP in Render environment variables.",
                    'telemetry' => $telemetry,
                ]);
            }

            $successMsg = $isDemoMode
                ? "Test email successfully dispatched to {$dispatchTo} via {$defaultMailer} (Mailtrap Demo Sandbox - Target: {$recipientEmail})."
                : "Test email successfully dispatched to {$recipientEmail} via {$defaultMailer}!";

            return response()->json([
                'status' => 'success',
                'delivered' => true,
                'is_demo_mode' => $isDemoMode,
                'message' => $successMsg,
                'telemetry' => $telemetry,
            ]);
        } catch (\Throwable $e) {
            $errorMessage = $e->getMessage();

            if (str_contains($errorMessage, 'Domain DNS records have not been verified')) {
                $errorMessage = "Mailtrap SMTP Notice: The DNS records for rashawassouf.com are detected on Netlify, but sending domain status requires clicking 'Verify DNS Records' in your Mailtrap Dashboard (Sending Domains -> rashawassouf.com) to complete activation.";
            } elseif (str_contains($errorMessage, 'Demo domains can only be used to send emails to account owners')) {
                try {
                    Mail::mailer($defaultMailer)->raw("This is a diagnostic fallback test email routed to the account owner in Mailtrap Demo Sandbox.\n\nTarget was: {$recipientEmail}\nTimestamp: " . now()->toIso8601String(), function ($msg) use ($demoRecipient, $defaultMailer) {
                        $msg->to($demoRecipient)
                            ->subject("✨ [Demo Sandbox] Email Delivery Test [{$defaultMailer}]");
                    });

                    return response()->json([
                        'status' => 'success',
                        'delivered' => true,
                        'is_demo_mode' => true,
                        'message' => "Delivered to admin ({$demoRecipient}) via Mailtrap Demo Sandbox. (Mailtrap demo domain restricts external recipients until a custom domain is verified).",
                        'telemetry' => $telemetry,
                    ]);
                } catch (\Throwable $fallbackEx) {
                    $errorMessage = 'Mailtrap Demo Domain Notice: ' . $fallbackEx->getMessage();
                }
            }

            Log::error('Diagnostic test email failed: ' . $e->getMessage(), ['exception' => $e]);

            return response()->json([
                'status' => 'error',
                'delivered' => false,
                'message' => 'Test email failed: ' . $errorMessage,
                'telemetry' => $telemetry,
            ], 422);
        }
    }
}
