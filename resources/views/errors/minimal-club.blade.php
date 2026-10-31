<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') - {{ config('app.name') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #12141a; color: #fff; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; text-align: center; padding: 24px; }
        .code { font-size: 96px; font-weight: 800; line-height: 1; color: #ffd600; letter-spacing: -2px; }
        h1 { font-size: 28px; margin: 16px 0 8px; }
        p { color: #a1a1aa; max-width: 32rem; margin: 0 auto 24px; line-height: 1.5; }
        a { display: inline-block; background: #45a0e0; color: #fff; padding: 12px 22px; border-radius: 999px; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main>
        <div class="code">@yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <a href="{{ url('/') }}">{{ __('Strona główna') }}</a>
    </main>
</body>
</html>
