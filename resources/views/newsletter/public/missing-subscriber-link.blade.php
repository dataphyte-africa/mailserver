<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscription link unavailable</title>
    <style>
        body { margin: 0; background: #f3f0e9; color: #182638; font-family: Georgia, serif; }
        main { max-width: 620px; margin: 12vh auto; padding: 36px; background: #fff; border: 1px solid #cbd2d4; border-radius: 12px; }
        h1 { margin-top: 0; }
        p { line-height: 1.6; }
    </style>
</head>
<body>
<main>
    <h1>{{ $mode === 'unsubscribe' ? 'Unsubscribe link unavailable' : 'Preferences link unavailable' }}</h1>
    <p>This link does not identify a saved subscriber. It may have come from an older test email. No subscription has been changed.</p>
    <p>For a current subscriber, use the link in a newly sent newsletter. Test emails now use read-only links instead.</p>
</main>
</body>
</html>
