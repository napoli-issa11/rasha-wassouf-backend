<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $emailSubject ?? 'Reply from Rasha Wassouf Design Studio' }}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0A0B0E; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #F5F5F7;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #0A0B0E; padding: 40px 15px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px; width: 100%; background-color: #12141A; border: 1px solid rgba(212, 175, 55, 0.4); border-radius: 8px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.8);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="padding: 40px 35px 25px; background: linear-gradient(180deg, #181A22 0%, #12141A 100%); border-bottom: 1px solid rgba(212, 175, 55, 0.2); text-align: center;">
                            <h1 style="margin: 0; font-family: 'Georgia', serif; font-size: 26px; color: #D4AF37; letter-spacing: 0.1em; text-transform: uppercase;">
                                Rasha Wassouf
                            </h1>
                            <p style="margin: 6px 0 0; font-size: 11px; color: #A0A0A0; letter-spacing: 0.25em; text-transform: uppercase;">
                                Architecture & Interior Design Studio
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 35px 35px 40px;">
                            @if(!empty($isDemoMode))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 24px; background: rgba(212, 175, 55, 0.08); border: 1px solid rgba(212, 175, 55, 0.35); border-left: 4px solid #D4AF37; border-radius: 4px;">
                                    <tr>
                                        <td style="padding: 14px 18px;">
                                            <p style="margin: 0 0 4px; font-size: 11px; color: #D4AF37; font-weight: 700; text-transform: uppercase; letter-spacing: 0.1em;">
                                                ✉ Mailtrap Demo Sandbox Mode • Routed to Admin
                                            </p>
                                            <p style="margin: 0; font-size: 12px; color: #C5C6D0; line-height: 1.5;">
                                                Target Client: <strong style="color: #FFFFFF;">{{ $intendedRecipient ?? 'Client' }}</strong> ({{ $clientName }}). Once your custom domain is connected, replies will be dispatched directly to client inboxes.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if(!empty($clientName))
                                <p style="margin: 0 0 18px; font-size: 16px; color: #FFFFFF; font-weight: bold;">
                                    Dear {{ $clientName }},
                                </p>
                            @endif

                            @if(!empty($projectCategory))
                                <div style="display: inline-block; padding: 4px 12px; margin-bottom: 20px; background-color: rgba(212, 175, 55, 0.12); border: 1px solid rgba(212, 175, 55, 0.3); border-radius: 4px; font-size: 12px; color: #D4AF37; letter-spacing: 0.05em; text-transform: uppercase;">
                                    Re: {{ $projectCategory }}
                                </div>
                            @endif

                            <!-- Message Body -->
                            <div style="font-size: 14px; color: #D2D2DA; line-height: 1.85; margin-bottom: 30px; word-break: break-word;">
                                {!! nl2br(e($replyMessage)) !!}
                            </div>

                            <hr style="border: none; border-top: 1px solid rgba(255, 255, 255, 0.08); margin: 30px 0 25px;" />

                            <!-- Signature -->
                            <p style="margin: 0; font-size: 14px; color: #FFFFFF; line-height: 1.6;">
                                Warm regards,<br>
                                <strong style="color: #D4AF37;">Rasha Wassouf & Studio Associates</strong><br>
                                <span style="font-size: 12px; color: #8A8C98;">Architectural Mastery & Haute Interior Environments</span><br>
                                <a href="mailto:studio@rashawassouf.com" style="color: #D4AF37; text-decoration: none; font-size: 12px;">studio@rashawassouf.com</a>
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
                                This transmission may contain confidential and privileged material. If you are not the intended recipient, please notify the sender immediately.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
