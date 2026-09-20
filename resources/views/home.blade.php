<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Your X account, run from any AI</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #0b0f14; color: #e8eef5; }
        main { max-width: 62rem; margin: 0 auto; padding: 3rem 1.5rem; }
        h1 { font-size: 2.25rem; margin: 0 0 0.75rem; line-height: 1.2; }
        h2 { font-size: 1.25rem; margin: 2.5rem 0 0.75rem; }
        .lede { font-size: 1.05rem; color: #b7c2ce; line-height: 1.55; margin: 0 0 1.5rem; }
        p { line-height: 1.5; color: #b7c2ce; }
        code, .box { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.85rem; }
        .box { display: block; padding: 0.85rem 1rem; background: #151b22; border: 1px solid #2a3440; border-radius: 0.5rem; overflow-wrap: anywhere; margin: 0.5rem 0 1.25rem; }
        .signed-in { background: #151b22; border: 1px solid #2a3440; border-radius: 0.5rem; padding: 1rem 1.25rem; margin: 1rem 0 2rem; font-size: 0.9rem; }
        a.button { display: inline-block; background: #1d9bf0; color: #061018; font-weight: 700; text-decoration: none; padding: 0.7rem 1.1rem; border-radius: 999px; }
        .muted { font-size: 0.9rem; color: #7a8898; }
        form { display: inline; }
        button.logout { background: transparent; color: #7a8898; border: 1px solid #2a3440; border-radius: 999px; padding: 0.4rem 0.8rem; cursor: pointer; font-size: 0.85rem; margin-left: 0.5rem; }

        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(15rem, 1fr)); gap: 1rem; margin-top: 1rem; }
        .tier { background: #151b22; border: 1px solid #2a3440; border-radius: 0.75rem; padding: 1.5rem; display: flex; flex-direction: column; }
        .tier.featured { border-color: #1d9bf0; position: relative; }
        .tier.featured::before { content: "MOST POPULAR"; position: absolute; top: -0.75rem; left: 1.25rem; background: #1d9bf0; color: #061018; font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.6rem; border-radius: 999px; letter-spacing: 0.05em; }
        .tier h3 { margin: 0 0 0.5rem; font-size: 1.15rem; }
        .price { font-size: 1.75rem; font-weight: 700; color: #e8eef5; margin-bottom: 0.25rem; }
        .price small { font-size: 0.75rem; color: #7a8898; font-weight: 400; }
        .annual-price { color: #7a8898; font-size: 0.85rem; margin-bottom: 1rem; }
        .tier ul { padding-left: 1.1rem; color: #b7c2ce; font-size: 0.9rem; margin: 0 0 1rem; flex-grow: 1; }
        .tier li { margin: 0.3rem 0; }
        a.pick { display: block; text-align: center; background: #1d9bf0; color: #061018; font-weight: 700; text-decoration: none; padding: 0.6rem 1rem; border-radius: 0.5rem; }
        a.pick.secondary { background: transparent; color: #b7c2ce; border: 1px solid #2a3440; margin-top: 0.5rem; font-weight: 500; font-size: 0.85rem; }
    </style>
</head>
<body>
<main>
    <h1>Talk to ChatGPT. It runs your X.</h1>
    <p class="lede">
        XConnect is the AI-native way to write, schedule, and analyze on X. Works with ChatGPT, Claude, and Grok.
        Built after Hypefury left X.
    </p>

    @auth
        <div class="signed-in">
            Signed in as <strong>{{ '@'.(auth()->user()->username ?: auth()->user()->name) }}</strong>
            @if (auth()->user()->subscription_status === 'active')
                on the <strong>{{ ucfirst(auth()->user()->subscription_tier) }}</strong> plan.
                <a href="{{ route('subscribe') }}" style="color:#1d9bf0">Manage subscription</a>
            @else
                — pick a plan below to activate.
            @endif
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="logout">Sign out</button>
            </form>
        </div>
    @endauth

    <h2>Pick your plan</h2>
    <p class="muted">All features on every tier. You only pick your monthly credit budget.</p>

    <div class="grid">
        <div class="tier">
            <h3>🌱 Publisher</h3>
            <div class="price">$19<small>/mo</small></div>
            <div class="annual-price">or $15/mo annual</div>
            <ul>
                <li>1,000 credits/mo</li>
                <li>~10 tweets/day</li>
                <li>Threads, articles, own analytics</li>
            </ul>
            <a class="pick" href="{{ route('start', ['tier' => 'publisher', 'cadence' => 'monthly']) }}">Start monthly</a>
            <a class="pick secondary" href="{{ route('start', ['tier' => 'publisher', 'cadence' => 'annual']) }}">Or annual — $180/yr</a>
        </div>

        <div class="tier featured">
            <h3>🔥 Pro</h3>
            <div class="price">$49<small>/mo</small></div>
            <div class="annual-price">or $35/mo annual</div>
            <ul>
                <li>3,000 credits/mo</li>
                <li>~20 tweets/day</li>
                <li>Competitor research, unlimited history</li>
            </ul>
            <a class="pick" href="{{ route('start', ['tier' => 'pro', 'cadence' => 'monthly']) }}">Start monthly</a>
            <a class="pick secondary" href="{{ route('start', ['tier' => 'pro', 'cadence' => 'annual']) }}">Or annual — $420/yr</a>
        </div>

        <div class="tier">
            <h3>⚡ Power</h3>
            <div class="price">$149<small>/mo</small></div>
            <div class="annual-price">or $109/mo annual</div>
            <ul>
                <li>10,000 credits/mo</li>
                <li>30+ tweets/day</li>
                <li>Heavy research, "no thinking about limits"</li>
            </ul>
            <a class="pick" href="{{ route('start', ['tier' => 'power', 'cadence' => 'monthly']) }}">Start monthly</a>
            <a class="pick secondary" href="{{ route('start', ['tier' => 'power', 'cadence' => 'annual']) }}">Or annual — $1,308/yr</a>
        </div>
    </div>

    <p class="muted" style="margin-top:1rem">One XConnect account = one X account. Ghostwriters: create one XConnect account per client.</p>

    <h2>How to connect (2 minutes)</h2>
    <ol style="color:#b7c2ce">
        <li>Pick a plan above. You'll sign in with X first if you haven't.</li>
        <li>After Stripe checkout, add this URL as a custom connector in ChatGPT, Claude, or Grok:</li>
    </ol>
    <code class="box">{{ $mcpUrl }}</code>

    <p class="muted">
        Try it in ChatGPT: <em>"Using XConnect, show me my top 10 posts of the last 30 days."</em>
    </p>

    <p class="muted" style="margin-top:3rem; border-top:1px solid #2a3440; padding-top:1rem">
        <a href="{{ route('privacy') }}" style="color:#7a8898">Privacy</a> ·
        <a href="{{ route('terms') }}" style="color:#7a8898">Terms</a> ·
        <a href="mailto:cyberandchill@gmail.com" style="color:#7a8898">Support</a>
    </p>
</main>
</body>
</html>
