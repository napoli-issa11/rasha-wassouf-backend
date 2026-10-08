<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAutoReply extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The project inquiry instance.
     */
    public ProjectInquiry $inquiry;

    /**
     * Create a new message instance.
     */
    public function __construct(ProjectInquiry $inquiry)
    {
        $this->inquiry = $inquiry;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Thank You for Contacting Rasha Wassouf Design Studio",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtml(),
        );
    }

    /**
     * Build the user-facing luxury confirmation email.
     */
    protected function buildHtml(): string
    {
        $name = htmlspecialchars($this->inquiry->full_name, ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($this->inquiry->project_category, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Contacting Rasha Wassouf</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0A0B0E; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #F5F5F7;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0A0B0E; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; width: 100%; background-color: #12141A; border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 8px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 35px 25px; background: linear-gradient(180deg, #181A22 0%, #12141A 100%); border-bottom: 1px solid rgba(212, 175, 55, 0.2); text-align: center;">
                            <h1 style="margin: 0; font-family: 'Georgia', serif; font-size: 26px; color: #D4AF37; letter-spacing: 0.1em; text-transform: uppercase;">Rasha Wassouf</h1>
                            <p style="margin: 6px 0 0; font-size: 11px; color: #A0A0A0; letter-spacing: 0.25em; text-transform: uppercase;">Architecture & Interior Design Studio</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 35px 40px;">
                            <p style="margin: 0 0 16px; font-size: 16px; color: #FFFFFF; font-weight: bold;">
                                Dear {$name},
                            </p>
                            <p style="margin: 0 0 20px; font-size: 14px; color: #D2D2DA; line-height: 1.8;">
                                Thank you for reaching out to <strong>Rasha Wassouf Design Studio</strong> regarding your upcoming <em>{$category}</em> commission.
                            </p>
                            <p style="margin: 0 0 24px; font-size: 14px; color: #D2D2DA; line-height: 1.8;">
                                We approach every project as a bespoke sanctuary of timeless form, craftsmanship, and structural elegance. Your initial brief has been securely cataloged and forwarded to our architectural team for preliminary spatial review.
                            </p>

                            <!-- Process Overview Box -->
                            <div style="background-color: #181A22; border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 6px; padding: 22px; margin-bottom: 25px;">
                                <h4 style="margin: 0 0 12px; font-size: 13px; color: #D4AF37; letter-spacing: 0.12em; text-transform: uppercase;">Next Steps</h4>
                                <ul style="margin: 0; padding-left: 18px; color: #C4C4CC; font-size: 13px; line-height: 1.7;">
                                    <li style="margin-bottom: 6px;"><strong>Brief Analysis:</strong> Reviewing your spatial context, scale, and vision.</li>
                                    <li style="margin-bottom: 6px;"><strong>Direct Contact:</strong> A senior architect will contact you within <strong>24 business hours</strong>.</li>
                                    <li><strong>Private Consultation:</strong> We will arrange an introductory concept dialogue or video consultation.</li>
                                </ul>
                            </div>

                            <p style="margin: 0 0 30px; font-size: 13px; color: #9C9EA8; font-style: italic; line-height: 1.6;">
                                "Architecture is frozen music, illuminated by living light."
                            </p>

                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; line-height: 1.6;">
                                Warm regards,<br>
                                <strong style="color: #D4AF37;">Rasha Wassouf & Studio Associates</strong><br>
                                <span style="font-size: 12px; color: #8A8C98;">Architectural Mastery & Haute Interior Environments</span>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 35px; background-color: #0D0E12; border-top: 1px solid rgba(255, 255, 255, 0.06); text-align: center;">
                            <p style="margin: 0 0 6px; font-size: 11px; color: #8A8C98;">
                                Architects Boulevard, Suite 400 • Private Consultations by Appointment Only
                            </p>
                            <p style="margin: 0; font-size: 10px; color: #585A66;">
                                This is an automated confirmation of your request. Please do not reply directly to this notice.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }
}
