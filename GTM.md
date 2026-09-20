# XConnect — Launch Plan

MVP is feature-complete. This doc is short on purpose.

---

## ✅ Done (product is built)

- 15 MCP tools, 92 tests passing, live on Laravel Cloud
- Legal pages (`/privacy`, `/terms`), credit system, subscription gate, cost caps, admin commands, DB backup, safety snapshots
- Multi-AI (ChatGPT + Claude + Grok)
- Stripe (3 tiers × monthly/annual, webhook, Customer Portal, `/subscribe`, `/billing`, one-click `/start/{tier}`)
- Landing page (`/`) with pitch + pricing + subscribe CTA

**No more code changes needed for MVP.**

---

## 🟡 Do YOURSELF before inviting anyone (dashboards, ~20 min)

- [ ] Set X API monthly cap: **$50** — developer.x.com
- [ ] Bump Cloud alert threshold: **$5 → $20** — cloud.laravel.com
- [ ] Paste `/privacy` and `/terms` URLs into X Developer Portal
- [ ] Enable Stripe Customer Portal — dashboard.stripe.com/test/settings/billing/portal
- [ ] Rotate the test Stripe keys (they were pasted in chat)

---

## 🟠 Launch: beta invites (do this, in this order)

1. **Write your list of 5-10 X writers you know personally** — actual names
2. **DM each one** — offer free beta access via `is_beta = true`
3. **Watch usage for 2 weeks** — check `php artisan x:usage` to see what they actually use
4. **Ask each: "would you pay $19 for this? Why or why not?"**
5. **Fix only what breaks** — don't build new features from vibes

Then decide whether to go public.

---

## 🟢 Going public later (only after beta signals demand)

- Create live-mode Stripe products (same script, live keys)
- Update Cloud env vars with live Stripe keys
- Add pinned demo tweet on your X profile
- Post daily using the tool itself ("today I asked XConnect to...")
- Show HN + Product Hunt in same week

---

## 🔴 Do NOT do yet

- In-app dashboard (users live in chat, `x-me` shows credits)
- `/manual` page (ChatGPT already knows the tools)
- Credit top-up flow (add if beta users complain)
- Landing polish / demo video (add before public launch)
- `x-feedback` tool
- Cross-platform (Bluesky, Threads)
- Multi-account per user
- OpenAI directory submission
- Referral program, team seats, evergreen recycling

---

## 💰 Cost safety

You are mathematically bounded:
- **X API**: server-side circuit breaker $5/day equiv + $50/mo hard cap (once you set it)
- **Cloud**: Flex 512MB caps at ~$6/mo, DB fixed at 0.25 CU
- **Stripe**: fixed % per transaction
- **Circuit breaker + per-user credit caps** make one user unable to lose you money

At 1,000 users worst case: ~$4,775/mo cost vs ~$33,000/mo revenue = ~85% margin.

---

## Pricing (locked)

| Tier | Price | Credits | For |
|---|---|---|---|
| 🌱 Publisher | $19/mo ($15 annual) | 1,000 | ~10 tweets/day |
| 🔥 Pro | $49/mo ($35 annual) | 3,000 | ~20 tweets/day + research |
| ⚡ Power | $149/mo ($109 annual) | 10,000 | "No thinking about limits" |

One XConnect account = one X account. Ghostwriters create one per client.

---

## Onboarding email (send after payment)

Subject: `You're in — XConnect setup (2 min)`

```
Welcome to XConnect. Setup takes 2 minutes:

1. In ChatGPT: Settings → Apps & Connectors → Advanced → Developer Mode ON
2. Add connector → URL: https://x-via-chatgpt-production-qku1g1.laravel.cloud/mcp
3. Auth: OAuth
4. Sign in with X when prompted
5. New chat: "using XConnect, show me my top 10 posts last month"

Your plan: [Publisher / Pro / Power]
Credits: [1,000 / 3,000 / 10,000] per month, resets on the 1st.

Heads-up: after new features ship, disconnect + reconnect the connector
so ChatGPT sees new tools.

Reply with questions.
— Jess
```

---

## Contact

Support: cyberandchill@gmail.com
