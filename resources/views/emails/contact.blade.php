<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New contact message</title>
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
                            <h1 style="margin:0 0 14px;font-size:21px;color:#1c1c1c;">New contact message</h1>
                            <p style="margin:0 0 18px;font-size:14px;line-height:1.6;color:#555555;">
                                <strong>{{ $senderName }}</strong> ({{ $senderEmail }}) wrote from the contact form:
                            </p>
                            <div style="background-color:#f7f7f3;border:1px solid #e2e2d8;border-radius:10px;padding:16px 18px;margin-bottom:24px;">
                                <p style="margin:0;font-size:14px;line-height:1.7;color:#333333;white-space:pre-wrap;">{{ $body }}</p>
                            </div>
                            <p style="margin:0;font-size:13px;color:#555555;">
                                Reply directly to this email — it goes straight back to <strong>{{ $senderName }}</strong>.
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
