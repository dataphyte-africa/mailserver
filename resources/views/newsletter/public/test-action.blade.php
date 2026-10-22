<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Test email link</title>
    <style>
        body { margin: 0; background: #f3f0e9; color: #182638; font-family: Georgia, serif; }
        main { max-width: 620px; margin: 12vh auto; padding: 36px; background: #fff; border: 1px solid #cbd2d4; border-radius: 12px; }
        h1 { margin-top: 0; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
<main>
    <h1>{{ $action === 'unsubscribe' ? 'Unsubscribe' : 'Manage preferences' }} test link</h1>
    <p>This link came from a test email for {{ $campaign->name }}. Test emails do not change anyone's subscription or preferences.</p>
    <p>In a real campaign email, this link opens the recipient's {{ $action === 'unsubscribe' ? 'unsubscribe page' : 'preference page' }}.</p>
</main>
</body>
</html>
