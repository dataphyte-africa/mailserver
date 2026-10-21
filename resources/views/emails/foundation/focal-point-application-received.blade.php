<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application received</title>
</head>
<body style="margin:0;padding:32px 16px;background:#eef3f9;color:#17233d;font-family:Arial,sans-serif;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;border-collapse:collapse;background:#fff;">
        <tr>
            <td style="padding:24px 32px;background:#fff;border-bottom:4px solid #17345e;"><img src="{{ $logoUrl }}" width="280" alt="Dataphyte Foundation" style="display:block;width:280px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;"></td>
        </tr>
        <tr>
            <td style="padding:32px;font-size:16px;line-height:1.6;">
                <h1 style="margin:0 0 20px;font-size:25px;line-height:1.25;">We received your application</h1>
                <p style="margin:0 0 16px;">Dear {{ $fullName }},</p>
                <p style="margin:0 0 16px;">Thank you for applying to be a State Focal Point for Dataphyte Foundation's 2027 election observation exercise.</p>
                <p style="margin:0 0 16px;">Your application reference is <strong>{{ $reference }}</strong>. Please keep it for your records.</p>
                <p style="margin:0 0 16px;">Submitting an application does not guarantee selection. We will contact applicants about their status after the application deadline.</p>
                <p style="margin:24px 0 0;">Dataphyte Foundation</p>
            </td>
        </tr>
    </table>
</body>
</html>
