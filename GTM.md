# XConnect — GTM & Launch Plan

Living doc. Update as things ship. Delete lines when done.

## Current status

- **Product**: functional MVP, used personally by owner. All 14 MCP tools working.
- **Users**: 1 (owner). No paying customers.
- **Distribution**: none — connector added manually via URL in ChatGPT Dev Mode.
- **Pricing**: not set. Recommended $19 Publisher / $49 Pro, credit-based (see below).
- **Legal**: no ToS, no Privacy Policy, no billing.

---

## X API pricing reality (as of Feb 2026)

X killed flat-rate tiers. Everyone is on **pay-per-use**. This shapes everything.

| Action | X's cost to us |
|---|---|
| Post a tweet (no link) | $0.015 |
| Post a tweet (with link) | **$0.20** (13x more!) |
| Send a DM | $0.015 (same as tweet) |
| Read your own tweet | $0.001 |
| Read someone else's tweet | $0.005 |
| Read a DM | $0.005 (each message) |
| Look up a user | $0.010 |

**Implications:**
- "Unlimited reads" only safe for OWNED reads (your own account)
- Foreign reads (competitor research) are expensive — must be capped
- Link posts are 13x more expensive — must be metered
- DMs cost same as tweets — fold into fair-use post cap
- **DM abuse = app ban**: X kills entire apps for DM spam, not just the user
- Old "Basic $200/mo for 3000 writes" tier no longer exists

---

## Pricing (credit-based, all features both tiers)

Two tiers, same features, differ only in monthly credit allowance. Credits self-regulate expensive actions naturally — no artificial feature gates.

### 🌱 Publisher — $19/mo ($15/mo annual, save 21%)
- **1,000 credits/mo**
- All features: post, schedule, thread, longform, delete, reply, media, DMs, articles, competitor research, own analytics
- Capacity: ~500 tweets, OR ~45 link posts, OR ~10 competitor scans, OR any mix
- Overage: $10 for 500 more credits

### 🔥 Pro — $49/mo ($35/mo annual, save 29%)
- **3,000 credits/mo**
- Same features as Publisher
- Capacity: ~1,500 tweets, OR ~135 link posts, OR ~30 competitor scans, OR any mix
- Overage: $10 for 500 more credits

### Credit values (transparent)

| Action | Credits |
|---|---|
| Post (no link) | 2 |
| Post (with link) | 22 |
| DM sent | 2 |
| Reply | 2 |
| Read own tweet | 1 (bundled per 5) |
| Read foreign tweet | 1 each |
| User lookup | 2 |
| Article publish | 3 |
| Own analytics scan | 5-30 |
| Competitor scan (90 days) | 50-100 |
| Scheduling | 0 (free — cost hits at publish) |

### Multi-account (ghostwriters, agencies)

One XConnect account = one X account = one subscription. Ghostwriters create multiple XConnect accounts (one per client). Ghostwriter with 3 clients = 3 subscriptions = $57-147/mo. Natural agency pricing without building agency features.

Add multi-account-per-user later if 5+ ghostwriters ask.

### Later (skip for MVP)
- Higher-tier Enterprise plan when demand appears
- Multi-account under one login (per-plugin URLs)

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

> **Note: not needed yet — owner is still testing solo.** Build these when: (1) inviting first beta users OR (2) opening paid signups. Whichever comes first. Do NOT skip any before charging money.

Order matters. Ship top-down.

- [ ] **Global daily read/write circuit breaker** — pauses tools if app-wide daily spend near budget
- [ ] **Per-user monthly post cap** — 200 Starter / 600 Pro fair use (DMs count as posts)
- [ ] **Competitor scan quota** — 3/mo Pro, tracked in DB
- [ ] **Days_back cap on foreign analytics** — 90d Pro, unlimited on own
- [ ] **24hr cache on analytics queries** — Redis or DB-backed
- [ ] **Thread cap: max 25 posts per thread** — prevents 100-tweet catastrophes
- [ ] **DM daily cap: 20/day per user** — X will ban you for DM spam
- [ ] **Identical-content DM block** — if same DM text sent to ≥3 recipients in 24hr, reject (existential — protects your whole app from getting banned)
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
- [ ] Payment Link for Publisher ($19/mo) + annual ($180/yr)
- [ ] Payment Link for Pro ($49/mo) + annual ($420/yr)
- [ ] Overage pack: $10 for 500 more credits
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
> "XConnect: your X account, run from any AI. ChatGPT, Claude, or Grok — it just works."

**Not competing on price.** OpenTweet ($12), PostWizard ($8) target AI developers and price-shoppers. XConnect targets solo X writers who live in AI chats — different market, different value prop, different price.

**Multi-AI support = biggest differentiator.** Same MCP URL, same subscription, works on:
- ChatGPT (Developer Mode connectors)
- Claude.ai (custom connectors, native since Anthropic invented MCP)
- Grok (Bring Your Own MCP, launched May 2026)

Nobody else in the X SaaS space works across all three.

Hypefury dropped X in August 2026 — their users are actively looking for a replacement. Ride that migration wave.

### Marketing hooks
- *"Talk to ChatGPT, Claude, or Grok. It runs your X."*
- *"Stop copy-pasting between your AI and X."*
- *"Built after Hypefury left. Made for the AI-first crowd."*
- *"One subscription, three AIs, one X account."*

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
- [ ] Expand `PassportClient::skipsAuthorization()` trusted domains to include `claude.ai` and `grok.com` (currently only chatgpt.com/chat.openai.com) — otherwise Claude/Grok users see a consent screen on first connect
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
