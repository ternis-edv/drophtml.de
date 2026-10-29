<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $status }} · @{{site_name}}</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 28px; background: #151c28; color: #d6deeb; font-family: system-ui, sans-serif; }
        main { width: min(100%, 600px); }
        .label { font-size: 11px; letter-spacing: .1em; color: #a5b4fc; }
        h1 { font-size: clamp(32px, 7vw, 54px); letter-spacing: -.04em; line-height: 1.1; }
        p { color: #92a3bc; line-height: 1.8; }
        code { display: block; overflow-wrap: anywhere; padding: 16px; background: #19212f; border: 1px solid #2a3445; border-radius: 7px; }
        a { display: inline-block; margin-top: 24px; padding: 12px 18px; border-radius: 5px; color: #19223e; background: #a5b4fc; text-decoration: none; }
        a:focus-visible { outline: 2px solid #fff; outline-offset: 4px; }
    </style>
</head>
<body>
    <main>
        <span class="label">@{{site_name}} / @{{status_code}}</span>
        <h1>{{ $title }}.</h1>
        <p>We couldn’t load this page. Check the address or return to our home page.</p>
        <code>@{{requested_path}}</code>
        <a href="@{{site_url}}">Back to home →</a>
    </main>
</body>
</html>
