<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>X login failed</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background: #0b0f14; color: #e8eef5; margin: 0; min-height: 100vh; display: grid; place-items: center; }
        main { width: min(32rem, calc(100% - 2rem)); }
        a { color: #1d9bf0; }
    </style>
</head>
<body>
<main>
    <h1>Could not sign in with X</h1>
    <p>{{ $message }}</p>
    <p><a href="{{ route('auth.x') }}">Try again</a> · <a href="{{ route('home') }}">Home</a></p>
</main>
</body>
</html>
