<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #0b0f14; color: #e8eef5; }
        main { max-width: 42rem; margin: 0 auto; padding: 3rem 1.5rem; }
        h1 { font-size: 1.75rem; margin: 0 0 0.5rem; }
        p { line-height: 1.5; color: #b7c2ce; }
        code, .box { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85rem; }
        .box { display: block; padding: 0.85rem 1rem; background: #151b22; border: 1px solid #2a3440; border-radius: 0.5rem; overflow-wrap: anywhere; margin: 0.5rem 0 1.25rem; }
        a.button { display: inline-block; background: #1d9bf0; color: #061018; font-weight: 700; text-decoration: none; padding: 0.7rem 1.1rem; border-radius: 999px; }
        .muted { font-size: 0.9rem; }
        form { display: inline; }
        button { background: transparent; color: #e8eef5; border: 1px solid #2a3440; border-radius: 999px; padding: 0.55rem 0.9rem; cursor: pointer; }
    </style>
</head>
<body>
<main>
    <h1>X via ChatGPT</h1>
    <p>ChatGPT connector for managing an X account after Sign in with X. This URL is the app; the connector endpoint is below.</p>

    @auth
        <p>Signed in as <strong>{{ '@'.(auth()->user()->username ?: auth()->user()->name) }}</strong>.</p>
        <p><a class="button" href="{{ route('subscribe') }}">Manage subscription</a></p>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Sign out</button>
        </form>
    @else
        <p><a class="button" href="{{ route('auth.x') }}">Sign in with X</a></p>
        <p class="muted">You will do this from ChatGPT the first time you connect. You can also test it here.</p>
    @endauth

    <h2>ChatGPT connector URL</h2>
    <code class="box">{{ $mcpUrl }}</code>

    <h2>X callback URL</h2>
    <p class="muted">Paste this exact value in the X Developer Portal as the Callback URI / Redirect URL.</p>
    <code class="box">{{ $callbackUrl }}</code>

    <p class="muted">CLI checklist: <code>php artisan x:status</code>. Health: <code>{{ url('/up') }}</code>.</p>

    <p class="muted"><a href="{{ route('privacy') }}">Privacy Policy</a> · <a href="{{ route('terms') }}">Terms of Service</a></p>
</main>
</body>
</html>
