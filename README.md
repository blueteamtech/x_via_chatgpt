# X via ChatGPT

Laravel ChatGPT connector: a user signs in with X (OAuth), then ChatGPT can manage that X account through MCP tools.

GitHub holds the code. **Laravel Cloud** is what ChatGPT actually talks to (`https://your-app.laravel.cloud/mcp`). Publishing the repo does not register the connector in ChatGPT.

This app is built for Laravel Cloud:

- Laravel 13, PHP 8.3+ (use **8.4** on Cloud to match local)
- Official `laravel/mcp` + `laravel/passport` (ChatGPT OAuth 2.1 / PKCE / DCR)
- Native X OAuth 2.0 PKCE (no Socialite; compatible with Laravel’s Guzzle 8)
- Database sessions and cache (Cloud database or KV store)
- Trusts Cloud/Cloudflare proxies and forces HTTPS when `APP_URL` is `https://`
- Passport keys from env so they survive Cloud deploys
- Health check at `/up`
- Home and OAuth screens do not depend on Vite

## CLI until you need a browser

Do everything in the terminal until a command tells you to log in.

| Step | CLI | Browser only for |
| --- | --- | --- |
| 1 | `gh auth login` | GitHub OAuth |
| 2 | `git` / `gh repo create` | — |
| 3 | `composer global require laravel/cloud-cli` then `cloud ship` | Laravel Cloud login + GitHub attach if prompted |
| 4 | `php artisan x:status` | X Developer Portal (copy Client ID/Secret, paste callback) |
| 5 | `cloud environment:variables` + `cloud deploy` | — |
| 6 | ChatGPT → Developer Mode → add connector | Sign in with X / ChatGPT OAuth |

### 1. Local app

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan passport:keys
php artisan x:status
php artisan serve
```

`php artisan x:status` prints the exact callback URL and ChatGPT `/mcp` URL.

### 2. GitHub

```bash
gh auth login
git add .
git commit -m "Add X ChatGPT Laravel connector."
gh repo create x_via_chatgpt --public --source=. --remote=origin --push
```

CI runs Pint + tests on every push (`.github/workflows/ci.yml`). After Cloud is connected you can add Actions secret `LARAVEL_CLOUD_DEPLOY_HOOK` so only green `main` deploys. Until then, Cloud’s default push-to-deploy is fine.

### 3. Laravel Cloud

```bash
composer global require laravel/cloud-cli
cloud ship
```

In that flow: create the Cloud app, attach a **database**, do **not** enable HTTP basic auth, set PHP 8.4, set env vars (see below), deploy. You get `https://….laravel.cloud`.

Then:

```bash
cloud environment:variables --action=set --key=APP_URL --value=https://YOUR_APP.laravel.cloud
cloud environment:variables --action=set --key=APP_KEY --value="the same key as local or a new cloud key"
cloud environment:variables --action=set --key=SESSION_SECURE_COOKIE --value=true
```

Paste Passport PEMs (from `storage/oauth-private.key` and `storage/oauth-public.key`) into `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY`. Do not rotate them on every deploy.

Do not set `DB_CONNECTION=sqlite` on Cloud. Use the database Cloud injects.

### 4. Gather X Developer Portal details (browser)

Open [developer.x.com](https://developer.x.com) → your Project → your App.

Copy / set:

1. **OAuth 2.0 Client ID** → `X_CLIENT_ID`
2. **OAuth 2.0 Client Secret** → `X_CLIENT_SECRET`
3. **User authentication settings**
   - App type: **Web App** (confidential)
   - App permissions: **Read and write** (and **Direct Messages** if you want DMs)
   - Callback URI / Redirect URL: exactly `https://YOUR_APP.laravel.cloud/auth/x/callback` (plus `http://127.0.0.1:8000/auth/x/callback` for local)
   - Website URL: `https://YOUR_APP.laravel.cloud`
4. Make sure the app is in the **pay-per-use / production** environment X requires for API calls

Then:

```bash
cloud environment:variables --action=set --key=X_CLIENT_ID --value=PASTE_ID
cloud environment:variables --action=set --key=X_CLIENT_SECRET --value=PASTE_SECRET
cloud environment:variables --action=set --key=X_REDIRECT_URI --value=https://YOUR_APP.laravel.cloud/auth/x/callback
cloud deploy
```

Local `.env` gets the same three values with the localhost callback.

X API usage is billed. Writes cost money.

### 5. ChatGPT (browser)

Needs ChatGPT Plus/Pro (Developer Mode).

1. Settings → Apps & Connectors → Advanced → Developer Mode
2. Create connector
3. URL: `https://YOUR_APP.laravel.cloud/mcp`
4. Authentication: **OAuth**
5. Connect → Sign in with X → Authorize ChatGPT

Ask ChatGPT: “Using the X connector, who am I?” then try a draft post.

## What OAuth can and cannot do

Can (if those scopes are enabled on the X app): posts, replies, delete, timeline, mentions, search, likes, reposts, bookmarks, follows, blocks, mutes, lists, DMs, image upload.

Cannot: password/email/2FA, billing, ads manager, Developer Portal.

## Tools

`x-me`, `x-read-feed`, `x-create-post`, `x-delete-post`, `x-engage`, `x-social`, `x-lists`, `x-direct-messages`, `x-upload-media`

## Tests

```bash
vendor/bin/pint --test
php artisan test
```
