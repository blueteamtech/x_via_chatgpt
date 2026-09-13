# XConnect — GTM & Launch Plan

Living doc. Update as things ship. Delete lines when done.

## Current status

- **Product**: functional MVP, used personally by owner. All 14 MCP tools working.
- **Users**: 1 (owner). No paying customers.
- **Distribution**: none — connector is added manually via URL in ChatGPT Dev Mode.
- **Pricing**: not set. Recommended $10 Read / $39 Pro / $99 Power.
- **Legal**: no ToS, no Privacy Policy, no billing.

---

## Pre-launch hardening (MUST-DO before any paid customer)

Order matters. Ship top-down.

- [ ] **Global daily write circuit breaker** — pauses new writes if within 10% of X monthly quota
- [ ] **Per-user monthly write cap** — enforce in code; Pro = 200/mo, then blocked with clear message
- [ ] **Thread cap: max 25 posts per thread** — prevents 100-tweet catastrophes
- [ ] **Analytics `days_back` capped by tier** — 90 days Pro, 365 Power
- [ ] **DM daily cap: 20/day per user** — X will kill your whole app for DM spam
- [ ] **Subscription active check middleware** — no active sub = tools return "not subscribed"
- [ ] **`php artisan user:suspend {id}` command** — react fast to abuse
- [ ] Bump `#[Version]` on every schema change so ChatGPT re-fetches tools

---

## Legal / compliance (MUST-DO)

- [ ] Privacy Policy hosted at `/privacy` (covers: X tokens stored encrypted, scheduled posts, analytics data)
- [ ] Terms of Service hosted at `/terms` (covers: no spam, no automation abuse, right to suspend, no liability for X actions, refund policy)
- [ ] Add Privacy Policy URL to X Developer Portal → App Settings
- [ ] Add Terms of Service URL to X Developer Portal
- [ ] Refund policy: 7 days full refund, no refunds after that; document publicly

---

## Payment setup (Path A — fastest)

- [ ] Stripe account created + activated
- [ ] Enable Stripe Radar (fraud protection)
- [ ] Payment Link created for Pro ($39/mo recurring)
- [ ] Payment Link for Read ($10/mo recurring)
- [ ] Payment Link for Power ($99/mo recurring)
- [ ] Enable Stripe Customer Portal (users manage own subscriptions)
- [ ] Zapier or manual: after payment → email user with MCP URL + setup instructions

## Payment setup (Path B — proper, month 2)

- [ ] `composer require laravel/cashier`
- [ ] Migration for subscriptions
- [ ] Webhook for payment events
- [ ] `/subscribe` page with checkout button
- [ ] Auto-provision access on successful payment

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
- [ ] Comparison page: XConnect vs Hypefury (SEO play)
- [ ] Show HN post: "Show HN: ChatGPT connector to run your X account"
- [ ] Product Hunt launch (same week as HN)
- [ ] DM 20 X-writers you know personally, invite to free beta
- [ ] Post daily using the tool itself

### Beta phase (before charging money)

- [ ] Invite 5-10 X writers you know
- [ ] Free access for 2 weeks in exchange for weekly feedback
- [ ] Track: what tools they actually use, what breaks, what they'd pay for
- [ ] Ask directly: "Would you pay $39/mo for this? Why or why not?"

### Success metrics (leading indicators)

- Weekly Active Users (WAU) — target 60%+ of paid users
- Writes per user per week — target 20+
- Churn — target <5% monthly
- NPS from beta users — target 40+

---

## Product polish (small stuff worth doing)

- [ ] Add `IsDestructive` annotation to: cancel schedule, delete list, block/mute users, delete article
- [ ] Verify Cloud DB backups are encrypted
- [ ] Add retry-once logic to failed scheduled publishes
- [ ] Article publish requires typed "confirm" (irreversible)
- [ ] Show published/failed history in `x-schedule-post list` (not just pending)
- [ ] Add `x-me` response detail: current monthly write usage + cap
- [ ] Consistent response shape across tools (some return `{data: [...]}`, some `{tweets: [...]}`)

---

## Pricing (recommended)

| Tier | Price | Includes | Cap |
|---|---|---|---|
| **Read** | $10/mo | Analytics, user lookup, search | 0 writes |
| **Pro** | $39/mo | Everything | 200 writes/mo |
| **Power** | $99/mo | Everything + longer analytics window | 800 writes/mo |

- No free tier (X API cost)
- No annual pricing until 10 paying users
- No overages initially — hard cap protects both sides

---

## Post-launch (add later, don't build yet)

- Evergreen post recycling (Hypefury killer feature)
- Cross-platform (Bluesky, Threads) — diversify away from X-only risk
- Team seats / agency plan
- Referral program
- Web dashboard for viewing analytics outside ChatGPT
- Sentry / error tracking beyond `tool_invocations` table
- Automated content suggestions
- OpenAI connector directory submission (only after real usage data)

---

## Vendor risks (accept, don't try to solve)

- X changes API terms → mitigation: add another platform later
- OpenAI kills MCP → mitigation: none, would need to rebuild
- Stripe freezes account → mitigation: keep documentation, have backup processor ready

---

## Contact / channels

- Support email: (set up something@yourdomain.com)
- Feedback channel: (Discord? Telegram? Email replies?)
- Public updates: your X account
