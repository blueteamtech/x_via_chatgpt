<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service — {{ config('app.name') }}</title>
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
    <h1>Terms of Service</h1>
    <p class="muted">Last updated: {{ date('F j, Y') }}</p>

    <p>By using XConnect, you agree to these terms. If you do not agree, do not use the service.</p>

    <h2>What XConnect does</h2>
    <p>XConnect is a service that lets you manage your X (Twitter) account through AI assistants like ChatGPT, Claude, and Grok. You authorize your X account via OAuth, choose a subscription, and use the connector inside your AI assistant.</p>

    <h2>Acceptable use</h2>
    <p>You may not use XConnect to:</p>
    <ul>
        <li>Send spam, unsolicited direct messages, or repetitive automated content.</li>
        <li>Post content that violates X&#39;s Terms of Service, Rules, or Automation policies.</li>
        <li>Impersonate others, engage in coordinated inauthentic behavior, or evade platform enforcement.</li>
        <li>Post illegal content, hate speech, harassment, or CSAM.</li>
        <li>Attempt to scrape, resell, or extract data beyond your normal API usage.</li>
        <li>Circumvent rate limits, credit caps, or other service protections.</li>
    </ul>

    <h2>Our right to suspend</h2>
    <p>We may suspend or terminate your account without refund if you violate these terms, if your usage triggers X&#39;s abuse detection systems that put our platform at risk, or if we detect fraudulent payment activity. We aim to notify you before suspension when possible.</p>

    <h2>Subscription and billing</h2>
    <ul>
        <li>Plans are billed monthly or annually via Stripe.</li>
        <li>Your subscription auto-renews unless you cancel.</li>
        <li>Fair use credit limits apply to each tier; overage top-ups are available.</li>
        <li>You can cancel anytime through the Stripe Customer Portal.</li>
    </ul>

    <h2>No refunds</h2>
    <p>All subscription payments are non-refundable. You may cancel at any time to prevent future billing, and your access continues until the end of your current billing period.</p>

    <h2>No liability for X actions</h2>
    <p>You are responsible for content posted through your X account. XConnect is a tool — you (via your AI assistant) direct all actions. We are not liable for:</p>
    <ul>
        <li>X suspending, restricting, or terminating your X account for your posted content.</li>
        <li>Consequences of AI-generated content posted through your account.</li>
        <li>Loss of followers, reach, or engagement.</li>
        <li>Errors made by the AI assistant when interpreting your instructions.</li>
    </ul>

    <h2>Service availability</h2>
    <p>We aim for high availability but do not guarantee 100% uptime. X&#39;s API, Laravel Cloud infrastructure, and third-party AI providers may experience outages outside our control.</p>

    <h2>Changes to the service</h2>
    <p>We may add, remove, or modify features. If a material change reduces the value of your subscription, we will notify you before it takes effect.</p>

    <h2>Governing law</h2>
    <p>These terms are governed by the laws of the United States. Disputes will be resolved in the courts of the operator&#39;s jurisdiction.</p>

    <h2>Contact</h2>
    <p>Email: <a href="mailto:cyberandchill@gmail.com">cyberandchill@gmail.com</a></p>
</main>
</body>
</html>
