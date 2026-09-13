# XConnect — GTM & Launch Plan

Living doc. Update as things ship. Delete lines when done.

## Current status

- **Product**: functional MVP, used personally by owner. All 14 MCP tools working.
- **Users**: 1 (owner). No paying customers.
- **Distribution**: none — connector added manually via URL in ChatGPT Dev Mode.
- **Pricing**: not set. Recommended $29 Starter / $59 Pro (see below).
- **Legal**: no ToS, no Privacy Policy, no billing.

---

## X API pricing reality (as of Feb 2026)

X killed flat-rate tiers. Everyone is on **pay-per-use**. This shapes everything.

| Action | X's cost to us |
|---|---|
| Post a tweet (no link) | $0.015 |
| Post a tweet (with link) | **$0.20** (13x more!) |
| Read your own tweet | $0.001 |
| Read someone else's tweet | $0.005 |
| Look up a user | $0.010 |

**Implications:**
- "Unlimited reads" only safe for OWNED reads (your own account)
- Foreign reads (competitor research) are expensive — must be capped
- Link posts are 13x more expensive — must be metered
- Old "Basic $200/mo for 3000 writes" tier no longer exists

---

## Pricing (two-tier feature-differentiated)

Feature differentiation, not volume-gated. Fair-use caps protect margin.

### 🌱 Starter — $29/mo ($19/mo annual, -34%)
- Post + schedule (any depth ahead)
- Threads any length
- Longform tweets (if Premium)
- Your own analytics (last 90 days only)
- Reply to mentions, send DMs to your followers
- **Fair use: ~200 posts/mo** (overage: $10 for 200 more)

### 🔥 Pro — $59/mo ($39/mo annual, -34%)
Everything in Starter, plus:
- **Competitor research** — analyze other accounts' top posts (3 scans/mo, $2 each after)
- **Articles** — draft, publish, manage long-form
- **Own analytics** — unlimited history (owned reads are cheap)
- **Priority scheduler**
- **Fair use: ~600 posts/mo** (overage: $10 for 200 more)

### Later (skip for MVP)
- Power tier ($199/mo) when ghostwriters/agencies show up
- Multi-account when demanded

---

## Cost protection (4-layer defense)

Mathematically prevents losing money on any user.

### Layer 1: Per-query cap
- `x-get-top-posts` on foreign accounts capped at 90d (Pro) / 180d (Power)
- Own analytics: unlimited (owned reads at $0.001 = cheap)
- Max per query: ~$5 foreign, ~$3 own

### Layer 2: Per-user monthly quota
- Starter: no foreign analytics + ~200 posts fair use
- Pro: 3 competitor scans/mo + ~600 posts fair use
- Overage: $2/scan or $10/200 posts (Stripe one-tap)

### Layer 3: 24-hour cache
- Same analytics query in 24hr = free from cache
- Reduces read costs 60-70% for typical usage
- User just sees faster responses

### Layer 4: Global circuit breaker
- If total daily read spend approaches monthly budget → analytics pause app-wide until midnight UTC
- Posting still works
- Prevents runaway/coordinated abuse

**Max cost per user (all caps hit):**
- Starter: ~$8 → revenue $27 → **floor margin $19**
- Pro: ~$37 → revenue $57 → **floor margin $20**

---

## Pre-launch hardening (MUST-DO before any paid customer)

Order matters. Ship top-down.

- [ ] **Global daily read/write circuit breaker** — pauses tools if app-wide daily spend near budget
- [ ] **Per-user monthly post cap** — 200 Starter / 600 Pro fair use
- [ ] **Competitor scan quota** — 3/mo Pro, tracked in DB
- [ ] **Days_back cap on foreign analytics** — 90d Pro, unlimited on own
- [ ] **24hr cache on analytics queries** — Redis or DB-backed
- [ ] **Thread cap: max 25 posts per thread** — prevents 100-tweet catastrophes
- [ ] **DM daily cap: 20/day per user** — X will ban you for DM spam
- [ ] **Subscription active check middleware** — no active sub = tools return "not subscribed"
- [ ] **`php artisan user:suspend {id}` command** — react fast to abuse
- [ ] **`x-me` shows current usage** — user can ask "how many posts left this month"
- [ ] Bump `#[Version]` on every schema change so ChatGPT re-fetches tools

---

## Legal / compliance (MUST-DO)

- [ ] Privacy Policy hosted at `/privacy` (covers: encrypted X tokens, scheduled posts, analytics data)
- [ ] Terms of Service at `/terms` (no spam, no abuse, suspension rights, X liability disclaimer, refund policy)
- [ ] Add Privacy Policy URL to X Developer Portal → App Settings
- [ ] Add Terms of Service URL to X Developer Portal
- [ ] Refund policy: 7 days full refund, no refunds after that; document publicly

---

## Payment setup

### Path A — fastest (launch this)
- [ ] Stripe account activated + Radar on
- [ ] Payment Link for Starter ($29/mo) + annual ($228/yr)
- [ ] Payment Link for Pro ($59/mo) + annual ($468/yr)
- [ ] Overage packs: $10 (200 extra posts), $2 (extra competitor scan)
- [ ] Stripe Customer Portal enabled
- [ ] Zapier or manual email after payment → MCP URL + setup

### Path B — proper (month 2, when >5 paying customers)
- [ ] `composer require laravel/cashier`
- [ ] Subscriptions migration + webhook
- [ ] `/subscribe` page with checkout
- [ ] Auto-provision access on payment
- [ ] Stripe metered billing for overage

---

## Onboarding email template

Subject: `You're in — XConnect setup (2 min)`

```
Welcome to XConnect. Here's how to connect it to ChatGPT:

1. Go to ChatGPT → Settings → Apps & Connectors → Advanced → Developer Mode ON
2. Add connector → URL: https://x-via-chatgpt-production-qku1g1.laravel.cloud/mcp
3. Auth: OAuth
4. Sign in with X when prompted
5. In a new chat, try: "Using XConnect, show me my top 10 posts of the last 30 days"

Heads-up: when we ship new features, disconnect + reconnect the connector so ChatGPT sees the new tools.

Your plan: [Starter / Pro]
Fair use: [200 / 600] posts/month, resets on the 1st.

Questions? Reply to this email.
```

---

## Marketing / GTM strategy

### Positioning
> "XConnect: the X-writer's tool that lives in ChatGPT. Built after Hypefury left."

Hypefury dropped X in August 2026 — their users are actively looking for a replacement.

### Content pillars (post daily on X)
- **"Today I asked XConnect to..."** — screenshot of ChatGPT convo + resulting post
- **Weekly analytics** — screenshot of top-posts result, commentary
- **Behind-the-scenes build** — dev diary posts
- **Solopreneur X-writing tips** — evergreen content

### Launch sequence
- [ ] Pinned demo tweet: 60-second screen recording of core use case
- [ ] Landing page: one screen — what it is, price, buy button, demo
- [ ] Comparison page: XConnect vs Hypefury (SEO play for migrating users)
- [ ] Show HN post: "Show HN: ChatGPT connector to run your X account"
- [ ] Product Hunt launch (same week as HN)
- [ ] DM 20 X-writers you know personally, invite to free beta
- [ ] Post daily using the tool itself

### Beta phase (before charging money)
- [ ] Invite 5-10 X writers you know
- [ ] Free access for 2 weeks in exchange for weekly feedback
- [ ] Track: what tools they actually use, what breaks, what they'd pay for
- [ ] Ask directly: "Would you pay $29/mo Starter or $59/mo Pro?"

### Success metrics (leading indicators)
- Weekly Active Users (WAU) — target 60%+ of paid users
- Posts per user per week — target 20+
- Churn — target <5% monthly
- NPS from beta users — target 40+

---

## Product polish (small stuff worth doing)

- [ ] Add `IsDestructive` annotation to: cancel schedule, delete list, block/mute users, delete article
- [ ] Verify Cloud DB backups are encrypted
- [ ] Add retry-once logic to failed scheduled publishes
- [ ] Article publish requires typed "confirm" (irreversible)
- [ ] Show published/failed history in `x-schedule-post list` (not just pending)
- [ ] `x-me` returns current post usage + cap
- [ ] Add `x-feedback` tool so users can send feedback from inside ChatGPT
- [ ] Consistent response shape across tools

---

## Post-launch (add later, don't build yet)

- Evergreen post recycling (Hypefury killer feature)
- Cross-platform (Bluesky, Threads) — diversify away from X-only risk
- Multi-account per user (unlocks Power/Agency tier)
- Referral program
- Web dashboard for viewing analytics outside ChatGPT
- Sentry / third-party error tracking
- OpenAI connector directory submission (only after real usage data)

---

## Vendor risks (accept, don't try to solve)

- X changes API pricing (again) → mitigation: hybrid pricing lets you pass through
- OpenAI kills MCP → mitigation: none, would need to rebuild
- Stripe freezes account → mitigation: keep docs, have backup processor
- X app suspension from user abuse → mitigation: fast suspend command, clean ToS

---

## Contact / channels

- Support email: (set up something@yourdomain.com)
- Feedback channel: `x-feedback` tool in ChatGPT (build this)
- Public updates: your X account
