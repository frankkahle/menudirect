<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Restaurant Website is Ready</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 40px 20px;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">Your Restaurant Website is Ready!</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Hi {{ $client->first_name ?? $client->name }},
                            </p>

                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Great news! We've built a professional website for <strong>{{ $site->business_name }}</strong> and it's ready for you to review.
                            </p>

                            <!-- Site Preview Box -->
                            <div style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <h3 style="color: #c2410c; margin: 0 0 15px; font-size: 18px;">Your Website</h3>
                                <p style="margin: 0 0 10px;">
                                    <strong>Preview URL:</strong><br>
                                    <a href="{{ $site->getPublicUrl() }}" style="color: #ea580c; text-decoration: none;">{{ $site->getPublicUrl() }}</a>
                                </p>
                                @if($site->phone)
                                <p style="margin: 10px 0 0; color: #666;">
                                    <strong>Phone:</strong> {{ $site->phone }}
                                </p>
                                @endif
                            </div>

                            <h2 style="color: #333; font-size: 20px; margin: 30px 0 15px;">What's Next?</h2>

                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                To activate your website and make it live, please complete these simple steps:
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 28px; height: 28px; background-color: #f97316; color: white; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold;">1</div>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #333;">{{ $isNewAccount ? 'Create Your Account' : 'Log In to Your Account' }}</strong><br>
                                                    <span style="color: #666; font-size: 14px;">
                                                        @if($isNewAccount)
                                                            We've created an account for you. Log in with your email and the temporary password below, then set a new password.
                                                        @else
                                                            Log in to your existing SOS Tech Portal account.
                                                        @endif
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0; border-bottom: 1px solid #eee;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 28px; height: 28px; background-color: #f97316; color: white; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold;">2</div>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #333;">Pay Your Invoice</strong><br>
                                                    <span style="color: #666; font-size: 14px;">Complete the payment for Invoice #{{ $invoice->invoice_number }} to activate your site.</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding: 10px 0;">
                                        <table role="presentation" cellspacing="0" cellpadding="0">
                                            <tr>
                                                <td style="width: 40px; vertical-align: top;">
                                                    <div style="width: 28px; height: 28px; background-color: #f97316; color: white; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold;">3</div>
                                                </td>
                                                <td style="vertical-align: top;">
                                                    <strong style="color: #333;">Your Site Goes Live!</strong><br>
                                                    <span style="color: #666; font-size: 14px;">Once payment is received, your website will be activated and visible to your customers.</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            @if($isNewAccount && $tempPassword)
                            <!-- Login Credentials Box -->
                            <div style="background-color: #fef3c7; border: 1px solid #fcd34d; border-radius: 8px; padding: 20px; margin: 25px 0;">
                                <h3 style="color: #92400e; margin: 0 0 15px; font-size: 16px;">Your Login Credentials</h3>
                                <p style="margin: 0 0 8px; font-family: monospace;">
                                    <strong>Email:</strong> {{ $client->email }}
                                </p>
                                <p style="margin: 0; font-family: monospace;">
                                    <strong>Temporary Password:</strong> {{ $tempPassword }}
                                </p>
                                <p style="margin: 15px 0 0; color: #92400e; font-size: 13px;">
                                    You'll be asked to create a new password when you first log in.
                                </p>
                            </div>
                            @endif

                            <!-- CTA Button -->
                            <div style="text-align: center; margin: 30px 0;">
                                <a href="{{ url('/login') }}" style="display: inline-block; background-color: #f97316; color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 6px; font-weight: bold; font-size: 16px;">
                                    Log In to Portal
                                </a>
                            </div>

                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 20px 0 0;">
                                If you have any questions or need help, just reply to this email and we'll be happy to assist.
                            </p>

                            <p style="color: #333; font-size: 16px; line-height: 1.6; margin: 20px 0 0;">
                                Thank you for choosing SOS Tech!
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9fafb; padding: 20px 30px; border-top: 1px solid #e5e7eb;">
                            <p style="color: #6b7280; font-size: 13px; margin: 0; text-align: center;">
                                SOS Tech<br>
                                <a href="https://sos-tech.ca" style="color: #f97316; text-decoration: none;">sos-tech.ca</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
