<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #0b0f14; color: #e8eef5; }
        main { max-width: 62rem; margin: 0 auto; padding: 3rem 1.5rem; }
        h1 { font-size: 2rem; margin: 0 0 0.5rem; }
        p { line-height: 1.55; color: #b7c2ce; }
        .status { padding: 0.75rem 1rem; background: #151b22; border: 1px solid #2a3440; border-radius: 0.5rem; margin: 1rem 0 2rem; font-size: 0.9rem; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr)); gap: 1rem; margin-top: 2rem; }
        .tier { background: #151b22; border: 1px solid #2a3440; border-radius: 0.75rem; padding: 1.5rem; }
        .tier.featured { border-color: #1d9bf0; }
        .tier h2 { margin: 0 0 0.5rem; font-size: 1.25rem; }
        .price { font-size: 2rem; font-weight: 700; color: #e8eef5; }
        .price small { font-size: 0.85rem; color: #7a8898; font-weight: 400; }
        .annual { color: #7a8898; font-size: 0.85rem; margin: 0.25rem 0 1rem; }
        ul { padding-left: 1.25rem; color: #b7c2ce; }
        li { margin: 0.35rem 0; }
        a.btn { display: block; text-align: center; background: #1d9bf0; color: #061018; font-weight: 700; text-decoration: none; padding: 0.75rem 1rem; border-radius: 0.5rem; margin-top: 1rem; }
        a.btn.secondary { background: transparent; color: #b7c2ce; border: 1px solid #2a3440; margin-top: 0.5rem; font-weight: 500; }
        .muted { color: #7a8898; font-size: 0.85rem; }
    </style>
</head>
<body>
<main>
    <p class="muted"><a href="{{ url('/') }}" style="color:#7a8898">← Back</a></p>
    <h1>Pick your plan</h1>
    <p>Every tier includes every feature. You only pick your monthly credit budget.</p>

    @if ($currentStatus === 'active')
        <div class="status">
            ✓ You're on the <strong>{{ ucfirst($currentTier) }}</strong> plan. Manage your subscription in the Stripe portal.
        </div>
    @elseif ($currentStatus === 'past_due')
        <div class="status">
            ⚠️ Your subscription payment failed. Update your card in Stripe to restore access.
        </div>
    @elseif ($currentStatus === 'cancelled')
        <div class="status">
            Your subscription was cancelled. Resubscribe below.
        </div>
    @endif

    <div class="grid">
        <div class="tier">
            <h2>🌱 Publisher</h2>
            <div class="price">$19<small>/mo</small></div>
            <div class="annual">or $15/mo annual (save 21%)</div>
            <ul>
                <li>1,000 credits/mo</li>
                <li>~10 tweets/day, threads, articles</li>
                <li>Light research</li>
            </ul>
            @if ($links['publisher_monthly'])
                <a class="btn" href="{{ $links['publisher_monthly'] }}">Subscribe monthly</a>
            @endif
            @if ($links['publisher_annual'])
                <a class="btn secondary" href="{{ $links['publisher_annual'] }}">Or annual — $180/yr</a>
            @endif
        </div>

        <div class="tier featured">
            <h2>🔥 Pro</h2>
            <div class="price">$49<small>/mo</small></div>
            <div class="annual">or $35/mo annual (save 29%)</div>
            <ul>
                <li>3,000 credits/mo</li>
                <li>~20 tweets/day, threads, articles</li>
                <li>Regular competitor research</li>
            </ul>
            @if ($links['pro_monthly'])
                <a class="btn" href="{{ $links['pro_monthly'] }}">Subscribe monthly</a>
            @endif
            @if ($links['pro_annual'])
                <a class="btn secondary" href="{{ $links['pro_annual'] }}">Or annual — $420/yr</a>
            @endif
        </div>

        <div class="tier">
            <h2>⚡ Power</h2>
            <div class="price">$149<small>/mo</small></div>
            <div class="annual">or $109/mo annual (save 27%)</div>
            <ul>
                <li>10,000 credits/mo</li>
                <li>30+ tweets/day, unlimited research</li>
                <li>"Don't want to think about limits"</li>
            </ul>
            @if ($links['power_monthly'])
                <a class="btn" href="{{ $links['power_monthly'] }}">Subscribe monthly</a>
            @endif
            @if ($links['power_annual'])
                <a class="btn secondary" href="{{ $links['power_annual'] }}">Or annual — $1,308/yr</a>
            @endif
        </div>
    </div>

    <p class="muted" style="margin-top:2rem">
        Payments are handled by Stripe. Signed in as <strong>@{{ $user->username ?: $user->name }}</strong>.
        <a href="{{ route('privacy') }}" style="color:#7a8898">Privacy</a> ·
        <a href="{{ route('terms') }}" style="color:#7a8898">Terms</a>
    </p>
</main>
</body>
</html>
