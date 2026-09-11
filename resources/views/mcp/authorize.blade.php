<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize {{ config('app.name') }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #0b0f14; color: #e8eef5; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        .card { width: min(28rem, calc(100% - 2rem)); background: #151b22; border: 1px solid #2a3440; border-radius: 1rem; padding: 1.5rem; }
        h1 { font-size: 1.25rem; margin: 0 0 0.5rem; }
        p { color: #b7c2ce; line-height: 1.45; }
        .row { display: flex; gap: 0.75rem; margin-top: 1.25rem; }
        button { flex: 1; border-radius: 999px; padding: 0.7rem; font-weight: 700; cursor: pointer; }
        .ok { background: #1d9bf0; color: #061018; border: 0; }
        .no { background: transparent; color: #e8eef5; border: 1px solid #2a3440; }
    </style>
</head>
<body>
<div class="card">
    <h1>Connect ChatGPT to X</h1>
    <p>Allow <strong>{{ $client->name }}</strong> to manage {{ '@'.($user->username ?: $user->name) }} with the X permissions you already approved.</p>
    <div class="row">
        <form method="POST" action="{{ route('passport.authorizations.deny') }}">
            @csrf
            @method('DELETE')
            <input type="hidden" name="state" value="{{ request('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button class="no" type="submit">Cancel</button>
        </form>
        <form method="POST" action="{{ route('passport.authorizations.approve') }}">
            @csrf
            <input type="hidden" name="state" value="{{ request('state') }}">
            <input type="hidden" name="client_id" value="{{ $client->id }}">
            <input type="hidden" name="auth_token" value="{{ $authToken }}">
            <button class="ok" type="submit">Authorize</button>
        </form>
    </div>
</div>
</body>
</html>
