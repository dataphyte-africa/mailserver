<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Resume application | Dataphyte Foundation</title>
    <style>body{margin:0;background:#f2f5fa;color:#142039;font:16px/1.5 "Avenir Next","Segoe UI",sans-serif}.card{max-width:560px;margin:12vh auto;padding:32px;background:#fff;border:1px solid #ced8e8;border-radius:16px;box-shadow:0 6px 24px #14294a0b}.logo{display:block;width:220px;max-width:100%;height:auto;margin-bottom:28px}h1{margin:0 0 10px;font-size:28px}p{color:#52617b}button{margin-top:12px;border:1px solid #142039;border-radius:9px;background:#142039;color:#fff;padding:11px 20px;font:inherit;font-weight:600;cursor:pointer}</style>
</head>
<body>
<main class="card">
    <img class="logo" src="{{ asset('assets/foundation/Dataphyte%20Foundation%20Logo.png') }}" alt="Dataphyte Foundation">
    <h1>Continue your application</h1>
    <p>Confirm that you want to resume your saved State Coordinator application on this device.</p>
    <form method="post" action="{{ route('focal-points.applications.drafts.resume.consume', compact('uuid', 'token')) }}">
        @csrf
        <button type="submit">Continue application</button>
    </form>
</main>
</body>
</html>
