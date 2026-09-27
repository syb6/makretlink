<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset your MarketLink password</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f0;font-family:Arial,Helvetica,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f0;padding:32px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="560" cellpadding="0" cellspacing="0" style="max-width:560px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#889721;border-radius:14px 14px 0 0;padding:26px 32px;text-align:center;">
                            <span style="color:#ffffff;font-size:22px;font-weight:bold;letter-spacing:1px;">
                                🌿 Market<span style="color:#d7e29b">Link</span>
                            </span>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="background-color:#ffffff;border:1px solid #e2e2d8;border-top:none;padding:36px 32px;">
                            <h1 style="margin:0 0 14px;font-size:21px;color:#1c1c1c;">Reset your password</h1>
                            <p style="margin:0 0 16px;font-size:14px;line-height:1.6;color:#555555;">
                                Hi{{ $userName ? ' ' . $userName : '' }}, we received a request to reset the password for your MarketLink account.
                            </p>
                            <p style="margin:0 0 24px;font-size:14px;line-height:1.6;color:#555555;">
                                Click the button below to choose a new password:
                            </p>
                            <!-- Button -->
                            <table role="presentation" cellpadding="0" cellspacing="0" align="center" style="margin:0 auto 26px;">
                                <tr>
                                    <td style="background-color:#a2b231;border-radius:6px;">
                                        <a href="{{ $resetUrl }}"
                                           style="display:inline-block;padding:13px 34px;color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;">
                                            Choose a new password
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <!-- Expiry callout -->
                            <div style="background-color:#fdf3e3;border:1px solid #ecd9b0;border-radius:10px;padding:13px 16px;margin-bottom:22px;">
                                <p style="margin:0;font-size:13px;color:#7a5c1e;">
                                    ⏳ <strong>This link expires in {{ $expiryMinutes }} minutes</strong> and can be used only once. Request a fresh one any time from the sign-in page.
                                </p>
                            </div>
                            <p style="margin:0 0 6px;font-size:12px;color:#888888;">
                                Button not working? Copy this link into your browser:
                            </p>
                            <p style="margin:0 0 18px;font-size:12px;word-break:break-all;color:#889721;">{{ $resetUrl }}</p>
                            <hr style="border:none;border-top:1px solid #eeeeee;margin:0 0 16px;">
                            <p style="margin:0;font-size:12px;color:#999999;">
                                Didn't request this? You can safely ignore this email — your current password keeps working.
                            </p>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="padding:18px;text-align:center;">
                            <span style="font-size:11px;color:#aaaaaa;">© {{ date('Y') }} MarketLink — Fresh from local farmers.</span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
