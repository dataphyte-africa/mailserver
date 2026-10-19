<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Applications closed</title>
    <style>
        :root{--ink:#142039;--muted:#536480;--blue:#2557a7;--line:#d4dfed}
        *{box-sizing:border-box}body{margin:0;min-height:100vh;background:radial-gradient(circle at 85% 5%,#d8e7fb 0,transparent 38%),#f2f5fa;color:var(--ink);font:16px/1.5 "Avenir Next","Segoe UI",sans-serif}
        main{width:min(100% - 32px,690px);margin:0 auto;padding:28px 0 70px}.brand{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:24px}.brand img{width:165px;max-height:72px;object-fit:contain}.brand span{font-size:11px;font-weight:800;letter-spacing:.13em;text-transform:uppercase;color:var(--blue)}
        .card{overflow:hidden;background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:0 20px 50px #1b3a6720}.top{height:13px;background:linear-gradient(90deg,#17315a,#3368aa)}.content{padding:clamp(25px,6vw,46px)}.status{display:inline-flex;align-items:center;gap:9px;padding:7px 12px;border-radius:99px;background:#e8eff9;color:#204782;font-size:12px;font-weight:800;letter-spacing:.08em;text-transform:uppercase}.status:before{content:"";width:8px;height:8px;border-radius:50%;background:#d48b43}h1{font-size:clamp(29px,5vw,42px);line-height:1.12;letter-spacing:-.035em;margin:22px 0 14px}p{color:var(--muted);margin:0 0 16px}.detail{border-top:1px solid var(--line);padding-top:20px;margin-top:28px;font-size:14px}.detail strong{color:var(--ink)}footer{text-align:center;color:var(--muted);font-size:13px;margin-top:28px}
        @media(max-width:480px){main{width:min(100% - 24px,690px);padding-top:18px}.brand img{width:135px}.brand span{text-align:right}.content{padding:26px 22px}}
    </style>
</head>
<body>
<main>
    <div class="brand">@if($logoUrl)<img src="{{ $logoUrl }}" alt="{{ $brandName }}">@else<strong>{{ $brandName }}</strong>@endif<span>Applications</span></div>
    <div class="card"><div class="top"></div><div class="content">
        <span class="status">Submissions closed</span>
        <h1>Applications are closed.</h1>
        <p><strong>{{ $title }}</strong></p>
        <p>{{ $message ?? 'Thank you for your interest. This application is not accepting new submissions right now.' }}</p>
        <p>If you already submitted, your application remains on record. Please check the organisation's official channels for any reopening or future opportunities.</p>
        @if($closesAt)<p class="detail">Scheduled closing time: <strong>{{ $closesAt->format('j F Y, g:i A') }} WAT</strong></p>@endif
    </div></div>
    <footer>{{ $brandName }}</footer>
</main>
</body>
</html>
