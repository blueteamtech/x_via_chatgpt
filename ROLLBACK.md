# How to Roll Back If Something Breaks

Written 2026-09-15 (before GTM launch). Everything works right now. This is your safety net.

## The "known-good" snapshot

Everything as of today is saved in these places:

| Where | Name | What it is |
|---|---|---|
| GitHub tag | `mvp-launch-v1.0` | Permanent bookmark of today's code |
| GitHub branch | `stable/mvp-launch` | Frozen copy that never changes |
| Laravel Cloud | Deployment `depl-a2bcc000-825a-4a15-a45a-073df80a01e5` | Today's live version on Cloud |
| Git commit | `d4b7d6c` | The exact commit hash |
| Database backup | **Point-in-time recovery, 7-day window** | Continuous DB backup via Neon |

If ANY future change breaks the site, use one of the methods below.

---

## Method 1: Cloud dashboard rollback (fastest, ~1 min)

**Use this if:** the live site is broken and you want it back NOW.

1. Go to [cloud.laravel.com](https://cloud.laravel.com)
2. Sign in
3. Click your app (`x-via-chatgpt`)
4. Click your environment (`production`)
5. Click **Deployments** in the left sidebar
6. Find the deployment dated 2026-09-15 with message "Position for multi-AI support..."
7. Click the three-dot menu → **Rollback to this deployment**
8. Confirm
9. Wait ~1 minute — site is back to today's version

This is the fastest fix. Nothing else changes.

---

## Method 2: Ask Claude to revert (5 min)

**Use this if:** you want the code AND the live site rolled back permanently.

Say to Claude:
> "Revert to the mvp-launch-v1.0 tag and redeploy."

Claude will:
1. Reset the main branch to that tag
2. Push to GitHub
3. Cloud auto-deploys the safe version
4. Confirm it's live

---

## Method 3: Cloud CLI rollback (if you're comfortable with terminal)

Run this exact command:

```bash
~/.composer/vendor/bin/cloud deployment:rollback depl-a2bcc000-825a-4a15-a45a-073df80a01e5
```

Confirms and rolls back.

---

## Database backup: point-in-time recovery (PITR)

**Your database is continuously backed up** with a 7-day rolling window. You can restore the database to ANY moment in the last 7 days (e.g., "restore to 5 minutes before I ran that bad migration").

**Cost:** ~$1-3/mo included in your database bill (retention_days = 7 is enabled).

### When to use PITR (not the code rollback)

| Problem | Fix |
|---|---|
| Bad code broke posting | Code rollback (Method 1 or 2 above) |
| Bad code AND bad data | Code rollback + PITR restore |
| Accidentally deleted user data | PITR restore |
| Bad migration corrupted a table | PITR restore |
| A user account got wiped | PITR restore |

### How to trigger a PITR restore

**Via Cloud dashboard:**
1. Go to your database in cloud.laravel.com
2. Look for **Restore** or **Point-in-time recovery**
3. Pick the date/time to restore to
4. Confirm — a new database branch is created at that point in time

**Via Claude:**
Say: *"Restore the database to [date and time] using PITR"* — Claude can trigger it via the Cloud API.

**Note:** Named snapshots (like `mvp-launch-2026-09-15`) are NOT available on your current DB tier — only continuous PITR is. Continuous is actually better because you can restore to any moment, not just at named save points.

---

## What "code rollback" does NOT undo

Rollback fixes the CODE. It does NOT touch:

- Database data (your scheduled posts, user accounts, articles stay)
- Environment variables in Cloud (X_CLIENT_SECRET etc. stay)
- Your X Developer Portal settings
- User OAuth connections (people stay signed in)
- Stripe subscriptions

That's usually what you want. If a bad code change broke posting, rolling back the code fixes it without wiping user data.

---

## What if the database schema changed and rollback breaks it?

Rare but possible. If a bad update added new database columns that the old code doesn't know about:

1. Rollback the code (Method 1)
2. Ask Claude: "The database schema is ahead of the code — help me handle it"
3. Claude will either roll back the migration OR make the old code handle the newer schema

---

## How to check what's live right now

Anytime, run:
```bash
~/.composer/vendor/bin/cloud deployment:list env-a2b7bf82-60ef-4194-92ed-423889e0f386 --json | head
```

Or check in the Cloud dashboard under Deployments — the top one with "Active" is live.

---

## Summary card (print or bookmark)

```
XConnect MVP Launch Snapshot
Date: 2026-09-15
Git tag: mvp-launch-v1.0
Git branch: stable/mvp-launch
Cloud deploy: depl-a2bcc000-825a-4a15-a45a-073df80a01e5
Database: PITR enabled, 7-day rolling window
Working: all 14 tools, 46 tests pass, ChatGPT + Claude + Grok compatible

Rollback CODE: cloud.laravel.com → Deployments → find 2026-09-15 → Rollback
Restore DATA:  cloud.laravel.com → Database → Restore to point in time
```
