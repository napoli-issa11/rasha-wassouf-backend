<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminInquiryNotification extends Mailable implements ShouldQueue
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
            subject: "✨ New Project Brief: {$this->inquiry->full_name} [{$this->inquiry->project_category}]",
            replyTo: [
                new Address($this->inquiry->email, $this->inquiry->full_name),
            ],
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
     * Build the luxury architectural email template.
     */
    protected function buildHtml(): string
    {
        $name = htmlspecialchars($this->inquiry->full_name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($this->inquiry->email, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($this->inquiry->telephone ?: 'Not provided', ENT_QUOTES, 'UTF-8');
        $category = htmlspecialchars($this->inquiry->project_category, ENT_QUOTES, 'UTF-8');
        $vision = nl2br(htmlspecialchars($this->inquiry->project_vision, ENT_QUOTES, 'UTF-8'));
        $date = $this->inquiry->created_at ? $this->inquiry->created_at->format('M d, Y - h:i A T') : now()->format('M d, Y - h:i A T');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Project Brief Inquiry</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0A0B0E; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #F5F5F7;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0A0B0E; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; width: 100%; background-color: #12141A; border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 8px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);">
                    <!-- Header -->
                    <tr>
                        <td style="padding: 35px 35px 25px; background: linear-gradient(180deg, #181A22 0%, #12141A 100%); border-bottom: 1px solid rgba(212, 175, 55, 0.2); text-align: center;">
                            <h1 style="margin: 0; font-family: 'Georgia', serif; font-size: 24px; color: #D4AF37; letter-spacing: 0.1em; text-transform: uppercase;">Rasha Wassouf</h1>
                            <p style="margin: 5px 0 0; font-size: 11px; color: #A0A0A0; letter-spacing: 0.25em; text-transform: uppercase;">Architecture & Interior Design Studio</p>
                            <div style="margin-top: 20px; display: inline-block; padding: 6px 14px; background-color: rgba(212, 175, 55, 0.15); border: 1px solid rgba(212, 175, 55, 0.35); border-radius: 4px; color: #D4AF37; font-size: 12px; font-weight: bold; letter-spacing: 0.08em;">
                                NEW PROJECT BRIEF RECEIVED
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 35px;">
                            <p style="margin: 0 0 25px; font-size: 14px; color: #D0D0D8; line-height: 1.6;">
                                A new prospective client has submitted an architectural project brief via the studio website. Details are outlined below:
                            </p>

                            <!-- Client Details Table -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 25px; background-color: #181A22; border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 6px;">
                                <tr>
                                    <td width="35%" style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #9E9E9E; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Client Name</td>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #FFFFFF; font-size: 14px; font-weight: bold;">{$name}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #9E9E9E; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Email Address</td>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #D4AF37; font-size: 14px;"><a href="mailto:{$email}" style="color: #D4AF37; text-decoration: none;">{$email}</a></td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #9E9E9E; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Telephone</td>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #FFFFFF; font-size: 14px;">{$phone}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #9E9E9E; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Category</td>
                                    <td style="padding: 12px 18px; border-bottom: 1px solid rgba(255, 255, 255, 0.06); color: #FFFFFF; font-size: 14px;">{$category}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 12px 18px; color: #9E9E9E; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">Submitted At</td>
                                    <td style="padding: 12px 18px; color: #A0A0A0; font-size: 12px;">{$date}</td>
                                </tr>
                            </table>

                            <!-- Project Vision -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="margin: 0 0 10px; font-size: 13px; color: #D4AF37; text-transform: uppercase; letter-spacing: 0.1em;">Project Vision & Requirements</h3>
                                <div style="background-color: #181A22; border-left: 3px solid #D4AF37; padding: 18px; border-radius: 0 6px 6px 0; color: #E8E8EE; font-size: 14px; line-height: 1.7;">
                                    {$vision}
                                </div>
                            </div>

                            <!-- Direct Reply Action -->
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="mailto:{$email}?subject=Regarding%20Your%20Project%20Brief%20-%20Rasha%20Wassouf%20Design%20Studio" style="display: inline-block; background-color: #D4AF37; color: #0D0D0D; font-weight: bold; font-size: 13px; padding: 12px 28px; text-decoration: none; border-radius: 4px; letter-spacing: 0.05em;">
                                    REPLY DIRECTLY TO CLIENT &rarr;
                                </a>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding: 20px 35px; background-color: #0D0E12; border-top: 1px solid rgba(255, 255, 255, 0.06); text-align: center;">
                            <p style="margin: 0; font-size: 11px; color: #6E707C;">
                                Confidential Admin Notification • Rasha Wassouf Portfolio CMS System
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
