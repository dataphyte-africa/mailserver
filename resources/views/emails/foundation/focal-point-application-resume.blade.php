<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resume your application</title>
</head>
<body style="margin:0;padding:32px 16px;background:#eef3f9;color:#17233d;font-family:Arial,sans-serif;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;border-collapse:collapse;background:#fff;">
        <tr>
            <td style="padding:24px 32px;background:#fff;border-bottom:4px solid #17345e;"><img src="{{ $logoUrl }}" width="190" alt="Dataphyte Foundation" style="display:block;width:190px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></td>
        </tr>
        <tr>
            <td style="padding:32px;font-size:16px;line-height:1.6;">
                <h1 style="margin:0 0 20px;font-size:25px;line-height:1.25;">Continue your application</h1>
                <p style="margin:0 0 20px;">You requested a secure link to resume your State Focal Point application.</p>
                <table role="presentation" style="border-collapse:collapse;margin:0 0 20px;">
                    <tr><td style="border-radius:8px;background:#17345e;"><a href="{{ $resumeUrl }}" style="display:inline-block;padding:12px 18px;color:#fff;font-weight:700;text-decoration:none;">Resume application</a></td></tr>
                </table>
                <p style="margin:0;">This link can be used once and expires in 30 minutes. If you did not request it, you can ignore this email.</p>
            </td>
        </tr>
    </table>
</body>
</html>
