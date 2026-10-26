<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email</title>
</head>
<body style="margin:0;padding:32px 16px;background:#eef3f9;color:#17233d;font-family:Arial,sans-serif;">
    <table role="presentation" style="width:100%;max-width:600px;margin:0 auto;border-collapse:collapse;background:#fff;">
        <tr><td style="padding:24px 32px;background:#fff;border-bottom:4px solid #17345e;"><img src="{{ $logoUrl }}" width="280" alt="Dataphyte Foundation" style="display:block;width:280px;max-width:100%;height:auto;border:0;"></td></tr>
        <tr><td style="padding:32px;font-size:16px;line-height:1.6;">
            <h1 style="margin:0 0 20px;font-size:25px;line-height:1.25;">Verify your email address</h1>
            <p style="margin:0 0 16px;">Use this code to save your State Coordinator application and continue:</p>
            <p style="margin:0 0 20px;font-size:28px;font-weight:700;letter-spacing:6px;color:#17345e;">{{ $code }}</p>
            <p style="margin:0;">This code expires in 10 minutes. Do not share it with anyone.</p>
        </td></tr>
    </table>
</body>
</html>
