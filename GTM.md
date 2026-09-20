# XConnect — GTM & Launch Plan

Living doc. Delete lines as things ship.

---

## 📋 STATUS BOARD (ELI5)

### ✅ Done — you can point to these as complete

- **Product works end-to-end** — 15 tools, all tested, live on Laravel Cloud
- **Legal pages live** — `/privacy` and `/terms` served publicly, linked from homepage
- **Credit system live** — every tool call deducts credits, caps per tier enforced
- **Subscription gate live** — new users need active sub; you (owner) grandfathered as beta
- **Cost caps live** — thread limits, DM caps, foreign analytics limits, global circuit breaker
- **Admin commands live** — `user:suspend`, `user:unsuspend`, `user:tier` from CLI
- **Multi-AI support confirmed** — ChatGPT, Claude, and Grok
- **Database backup live** — 7-day point-in-time recovery, ~$1-3/mo
- **Safety snapshots taken** — git tag `mvp-launch-v1.0`, branch `stable/mvp-launch`, `ROLLBACK.md`
- **CI + local check command** — `composer check` runs pint + 92 tests before every push
- **Stripe integration live** — 3 tiers × monthly/annual = 6 Payment Links, webhook wired, activates users
- **Stripe Customer Portal wired** — `/billing` route sends users to Stripe to manage sub
- **Landing page live** — pitch + 3 tier cards with one-click subscribe (`/start/{tier}/{cadence}`)
- **`/subscribe` page live** — shows active plan + tier picker

### 🟡 Do these YOURSELF in dashboards (no code, ~15 min total)

- [ ] **Set X API monthly spend cap: $50** — developer.x.com → billing
- [ ] **Bump Cloud alert threshold: $5 → $20** — cloud.laravel.com → billing
- [ ] **Paste `/privacy` and `/terms` URLs into X Developer Portal**
- [ ] **Enable Stripe Customer Portal** — dashboard.stripe.com/test/settings/billing/portal → configure switchable tiers → save
- [ ] **Rotate the test Stripe keys** — they were pasted in chat, best practice to rotate

### 🟠 Do NEXT (small code, ~1-2 hours each)

- [ ] **`/manual` page** (~1 hour) — the product's `--help`. All 15 tools grouped, example prompts by intent, per-AI setup, credit costs, FAQ. Highest UX value.
- [ ] **Credit top-up mechanism** (~30 min) — $10 = 500 credits Stripe Payment Link + webhook handler + link in "credits exhausted" ChatGPT error message
- [ ] **Landing page polish** (~30 min) — "How it works" 3-step section, link to `/manual`, better demo section
- [ ] **Onboarding email template** — draft the "welcome, here's your URL" email
- [ ] **Record a demo video** — 60-second screen recording

### 🔵 Do LATER (only after 5+ real users)

- [ ] Security review + Semgrep scan
- [ ] Rate limit middleware on `/mcp` route
- [ ] Response size warnings
- [ ] `x-feedback` tool for in-ChatGPT feedback
- [ ] Cross-platform (Bluesky, Threads)
- [ ] Multi-account per user
- [ ] OpenAI connector directory submission

### 🔴 Actively decide NOT to do — MVP-clarified

- **In-app dashboard** — users live in chat; credit balance already in `x-me`, billing already in Stripe portal. Skip.
- **Credit visualization UI** — moved to `x-me` (already returns tier + usage). No web UI needed.
- **In-app account settings** — Stripe portal handles it.
- **Evergreen recycling** — Hypefury killer feature but not MVP
- **Referral program** — pointless without paying users
- **Team seats / agency plan** — no demand yet
- **Complex staging/multi-environment** — over-engineering

### 🌐 Final web footprint (small on purpose)

| Page | Purpose |
|---|---|
| `/` | Pitch + pricing + one-click subscribe |
| `/manual` | Product help / examples (**to build**) |
| `/subscribe` | Stripe checkout entry |
| `/billing` | Redirect to Stripe portal |
| `/privacy` + `/terms` | Legal |
| `/mcp`, `/oauth/*`, `/auth/x`, `/stripe/webhook` | Plumbing (invisible) |

Everything else lives in chat via ChatGPT/Claude/Grok tools.

---

## 💰 Cost & security hardening (surprise-cost audit)

### What CAN still surprise you

| Source | Worst case | Current protection |
|---|---|---|
| X API abuse | Unlimited if no cap set | Server-side circuit breaker $5/day equiv. **You must set $50 cap in X dev portal.** |
| Stripe chargebacks | ~$15/dispute + account risk | Stripe Radar helps. No hard block. |
| DDoS bandwidth | Cloud charges over 2.5GB | Cloudflare in front handles basic DDoS |
| Third-party API abuse | Unlimited if no cap set | You set caps manually per service |

### What CAN'T surprise you (already bounded)

| Source | Cap |
|---|---|
| App compute | `max_replicas: 1`, Flex 512MB, ~$6/mo max |
| Database compute | `cu_max: 0.25`, fixed size |
| Database retention | 7 days PITR, small storage |
| Stripe fees | Fixed % per transaction |
| Internal credit spend | $5/day equivalent circuit breaker |

### ⚠️ Laravel Cloud does NOT auto-shut off at budget

Cloud has ALERT thresholds only — it notifies you, doesn't block. You have to react. X API DOES support hard blocking at your spend cap.

### Additional guardrails to build LATER (not now)

- HTTP rate limit on `/mcp` route (60 req/min per IP)
- Response size warnings (log if any response > 500KB)

---

## 🧪 Test coverage plan

**Right now: 77 tests passing** — tools, credits, gate, admin commands, circuit breaker, DM guard, thread caps, legal pages.

### Add in Phase 4 (Stripe integration)
- Webhook signature verification
- Subscription state transitions
- Stripe test-mode integration

### Add later (after 5+ users)
- HTTP smoke test of all public routes
- Scheduler command end-to-end
- Concurrent credit deduction race protection
- Full security scan (Semgrep + manual review)

---

## X API pricing reality (as of Feb 2026)

X killed flat-rate tiers. Everyone is on **pay-per-use**.

| Action | X's cost to us |
|---|---|
| Post a tweet (no link) | $0.015 |
| Post a tweet (with link) | **$0.20** (13x more!) |
| Send a DM | $0.015 |
| Read your own tweet | $0.001 |
| Read someone else's tweet | $0.005 |
| Read a DM | $0.005 |
| Look up a user | $0.010 |

Key facts:
- Old "$200/mo Basic" tier no longer exists
- Owned reads (your account) are cheap
- Foreign reads (competitors) are 5x more expensive
- Link posts are 13x regular posts
- DM abuse = whole-app ban (not just user)

---

## Pricing — locked in

Three tiers, same features, differ only in monthly credits.

| Tier | Price | Credits | For |
|---|---|---|---|
| 🌱 **Publisher** | $19/mo ($15 annual) | 1,000 | Casual daily writer, 10 tweets/day |
| 🔥 **Pro** | $49/mo ($35 annual) | 3,000 | Serious writer, 20 tweets/day + research |
| ⚡ **Power** | $149/mo ($109 annual) | 10,000 | Heavy user, "don't want to think about limits" |

### Credit values (already coded)

| Action | Credits |
|---|---|
| Post (no link) | 2 |
| Post (with link) | 22 |
| DM sent | 2 |
| Reply | 2 |
| Read own tweet | 1 |
| Read foreign tweet | 1 |
| User lookup | 2 |
| Article publish | 3 |
| Own analytics scan | 5-30 |
| Competitor scan (90 days) | 50-100 |
| Scheduling | 0 (free — cost hits at publish) |

### Multi-account (ghostwriters)
One XConnect account = one X account = one subscription. Ghostwriter with 3 clients = 3 subscriptions naturally = $57-147/mo. No agency features to build until 5+ ghostwriters ask.

### Cost protection (all live in production)
1. **Per-user monthly credit cap** — hard block at tier allowance
2. **Global daily circuit breaker** — pauses everything if daily spend > $5 equiv
3. **Thread cap** — max 25 posts per thread
4. **Foreign analytics cap** — max 180 days back on competitor scans
5. **DM caps** — 20/day per user + block identical text to 3+ recipients

---

## Payment setup (Phase 4 — pending Stripe keys)

### Path A — fastest (recommended for launch)
- [ ] Stripe account activated + Radar on (you have Stripe already?)
- [ ] Payment Link for Publisher ($19/mo) + annual ($180/yr)
- [ ] Payment Link for Pro ($49/mo) + annual ($420/yr)
- [ ] Payment Link for Power ($149/mo) + annual ($1,308/yr)
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
Welcome to XConnect. Setup takes 2 minutes:

1. In ChatGPT: Settings → Apps & Connectors → Advanced → Developer Mode ON
2. Add connector → URL: https://x-via-chatgpt-production-qku1g1.laravel.cloud/mcp
3. Auth: OAuth
4. Sign in with X when prompted
5. New chat, try: "using XConnect, show me my top 10 posts last month"

Your plan: [Publisher / Pro / Power]
Credits: [1,000 / 3,000 / 10,000] per month, resets on the 1st.

Heads-up: when we ship new features, disconnect + reconnect the connector so ChatGPT sees the new tools.

Reply with any questions.
— Jess
```

---

## Marketing / GTM

### Positioning
> "XConnect: your X account, run from any AI. ChatGPT, Claude, or Grok — it just works."

**Not competing on price.** Different market from OpenTweet ($12) or PostWizard ($8) — they target developers/price-shoppers, XConnect targets solo X writers who live in AI chats.

**Multi-AI = biggest moat.** Nobody else does this. Hypefury dropped X in August 2026 — their users are actively looking for a replacement.

### Marketing hooks
- *"Talk to ChatGPT, Claude, or Grok. It runs your X."*
- *"Stop copy-pasting between your AI and X."*
- *"Built after Hypefury left."*
- *"One subscription, three AIs, one X account."*

### Content pillars (post daily on X)
- **"Today I asked XConnect to..."** — screenshot + result
- **Weekly analytics** — top-posts screenshot + commentary
- **Behind-the-scenes build** — dev diary
- **Solopreneur X-writing tips** — evergreen

### Launch sequence
- [ ] Pinned demo tweet (60-sec screen recording)
- [ ] Landing page (one screen: what/price/buy/demo)
- [ ] Comparison page: XConnect vs Hypefury for X users (SEO)
- [ ] Show HN: "ChatGPT connector to run your X account"
- [ ] Product Hunt launch (same week as HN)
- [ ] DM 20 X-writers you know personally, invite to beta
- [ ] Post daily using the tool itself

### Beta phase (before charging)
- [ ] Invite 5-10 X writers you know
- [ ] Free access for 2 weeks, weekly feedback
- [ ] Track: tools used, breakages, willingness to pay
- [ ] Ask directly: "Would you pay $19 Publisher or $49 Pro?"

### Success metrics
- WAU % of paid users: target 60%+
- Posts/user/week: target 20+
- Churn: target <5%/mo
- NPS from beta: target 40+

---

## Product polish (all optional, do when convenient)

- [ ] Add `IsDestructive` annotation to: cancel schedule, delete list, block/mute, delete article
- [ ] Expand trusted OAuth domains to include `claude.ai` and `grok.com`
- [ ] Add retry-once logic to failed scheduled publishes
- [ ] Article publish requires typed "confirm"
- [ ] Show published/failed history in `x-schedule-post list`
- [ ] Add `x-feedback` tool for in-ChatGPT feedback
- [ ] Consistent response shape across tools

---

## Vendor risks (accept, don't solve)

- X changes API pricing → hybrid pricing lets us pass through
- OpenAI kills MCP → Claude and Grok still work
- Stripe freezes account → keep docs, have backup processor
- X app suspension from user abuse → fast suspend command + clean ToS

---

## Contact

- Support: cyberandchill@gmail.com
- Public updates: your X account (@cyberandchill)
