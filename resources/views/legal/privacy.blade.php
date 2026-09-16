<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy — {{ config('app.name') }}</title>
    <style>
        :root { color-scheme: light dark; }
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 0; background: #0b0f14; color: #e8eef5; }
        main { max-width: 42rem; margin: 0 auto; padding: 3rem 1.5rem; }
        h1 { font-size: 1.75rem; margin: 0 0 1rem; }
        h2 { font-size: 1.15rem; margin: 2rem 0 0.5rem; color: #e8eef5; }
        p, li { line-height: 1.55; color: #b7c2ce; }
        a { color: #1d9bf0; }
        .muted { font-size: 0.85rem; color: #7a8898; }
        ul { padding-left: 1.25rem; }
    </style>
</head>
<body>
<main>
    <p class="muted"><a href="{{ url('/') }}">← Back</a></p>
    <h1>Privacy Policy</h1>
    <p class="muted">Last updated: {{ date('F j, Y') }}</p>

    <p>This Privacy Policy describes how XConnect (&quot;we,&quot; &quot;our&quot;) collects, uses, and protects your information when you use our service to manage your X (Twitter) account through ChatGPT, Claude, Grok, or other AI assistants.</p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>X account data</strong>: your X user id, username, display name, and OAuth access tokens (stored encrypted at rest).</li>
        <li><strong>Content you create</strong>: scheduled posts, article drafts, and metadata you send through our tools.</li>
        <li><strong>Usage data</strong>: which tools you invoke, timestamps, success/failure status, error messages. Used for reliability, billing, and product improvement.</li>
        <li><strong>Payment information</strong>: processed by Stripe. We store your customer id and subscription status, not your card details.</li>
    </ul>

    <h2>What we do NOT collect</h2>
    <ul>
        <li>Your X password (we use OAuth — you sign in on X directly).</li>
        <li>Your ChatGPT / Claude / Grok conversation content beyond the specific tool arguments you invoke.</li>
        <li>Any data from X accounts you do not own or are not connecting.</li>
    </ul>

    <h2>How we use your data</h2>
    <ul>
        <li>To perform the X actions you request through your AI assistant.</li>
        <li>To bill you for usage under your chosen subscription tier.</li>
        <li>To monitor system health, detect abuse, and improve reliability.</li>
        <li>To send you service emails (payment receipts, usage alerts, service updates).</li>
    </ul>

    <h2>Third parties</h2>
    <ul>
        <li><strong>X (Twitter)</strong>: we send API requests on your behalf using tokens you authorized. Your X activity is subject to X&#39;s Terms of Service and Privacy Policy.</li>
        <li><strong>Stripe</strong>: handles payment processing.</li>
        <li><strong>Laravel Cloud</strong>: hosts the application and database.</li>
        <li><strong>OpenAI / Anthropic / xAI</strong>: your AI assistant provider. We do not control what these companies do with your conversations.</li>
    </ul>

    <h2>Data retention</h2>
    <p>We retain your account data as long as your subscription is active. If you cancel, we retain your data for 30 days in case you reactivate, then delete it. You may request immediate deletion by emailing us.</p>

    <h2>Your rights</h2>
    <ul>
        <li>Access, correct, or delete your data at any time by emailing us.</li>
        <li>Export your scheduled posts, drafts, and usage history on request.</li>
        <li>Revoke X access anytime via your X account settings.</li>
    </ul>

    <h2>Security</h2>
    <p>X OAuth tokens are stored encrypted at rest. Backups are enabled with 7-day point-in-time recovery. We follow standard practices for a solo-operated SaaS. No system is perfectly secure — if you notice a security issue, please email us.</p>

    <h2>Changes</h2>
    <p>If we make material changes to this policy, we will notify active subscribers by email.</p>

    <h2>Contact</h2>
    <p>Email: <a href="mailto:{{ config('app.support_email', 'support@example.com') }}">{{ config('app.support_email', 'support@example.com') }}</a></p>
</main>
</body>
</html>
