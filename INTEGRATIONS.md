# Connecting MapX to real services

Out of the box MapX runs in **demo mode** (`MAPX_INTEGRATIONS_MOCK=true`) and
**sandbox billing** (no Stripe keys): every feature works, but connections are
simulated and subscriptions activate without charging. This guide is the
checklist for going real. Each section ends with the exact env vars to set on
your host (Laravel Cloud → Settings → Custom environment variables).

> After changing env vars, always **redeploy** — the running app only reads
> them at boot.

---

## 1. Google Business Profile (locations, reviews, posts, insights)

MapX uses the industry-standard "approach B": the customer signs in with
Google, MapX lists every location they already manage, and they import them
as branches in one click (the same flow Yext/Uberall use). Manual branch
creation and CSV import remain available for businesses not on Google yet.

### One-time setup (you, as the platform owner)

1. Create a project at [console.cloud.google.com](https://console.cloud.google.com).
2. **Request Business Profile API access** — this is a formal application via
   Google's [access request form](https://developers.google.com/my-business/content/prereqs).
   Approval typically takes several days and requires answering how your
   platform uses the APIs. Until approved, API calls return quota errors
   (`PERMISSION_DENIED` / 0 QPS quota).
3. Once approved, enable these APIs on the project:
   - My Business Account Management API
   - My Business Business Information API
   - Business Profile Performance API
4. Configure the **OAuth consent screen** (External, publish it), then create
   an **OAuth client ID** (type: Web application) with this authorized
   redirect URI:
   ```
   https://YOUR-DOMAIN/integrations/google/callback
   ```

### Env vars

```dotenv
MAPX_INTEGRATIONS_MOCK=false
GOOGLE_CLIENT_ID=xxxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-xxxxxxxx
```

### What the customer then sees

Integrations → **Connect with Google** → Google sign-in + consent →
**Import locations** screen listing all their Business Profile locations →
selected locations become branches (name, address, coordinates, phone, hours,
categories, place ID) with live sync links.

---

## 2. Meta — Facebook Pages & Instagram Business (posts, insights)

1. Create an app at [developers.facebook.com](https://developers.facebook.com)
   (type: Business).
2. Add the **Facebook Login** product; set the OAuth redirect URI:
   ```
   https://YOUR-DOMAIN/integrations/meta/callback
   ```
3. Request these permissions via **App Review** (required before non-admin
   users can grant them): `pages_show_list`, `pages_read_engagement`,
   `pages_manage_posts`, `pages_manage_engagement`, `instagram_basic`,
   `instagram_content_publish`.
   While the app is in Development Mode, only app admins/testers can connect —
   use that for testing before review passes.

```dotenv
META_APP_ID=xxxxxxxx
META_APP_SECRET=xxxxxxxx
```

Customer flow: **Connect with Facebook** → page list → map each page to a
branch (linked Instagram business accounts connect automatically).

---

## 3. Stripe — real subscription payments

With Stripe keys set, the Subscribe buttons send customers to **Stripe's
hosted checkout** (card entry, 3-D Secure verification). Webhooks keep local
state truthful: activation, renewals, failed payments (which lock features),
and cancellations.

1. Create an account at [stripe.com](https://stripe.com) — use **test mode
   keys first**; test card `4242 4242 4242 4242` completes checkout without
   real money.
2. Developers → API keys → copy the publishable + secret keys.
3. Developers → Webhooks → Add endpoint:
   ```
   https://YOUR-DOMAIN/webhooks/stripe
   ```
   Events to send: `checkout.session.completed`, `invoice.paid`,
   `invoice.payment_failed`, `customer.subscription.deleted`.
   Copy the signing secret.

```dotenv
STRIPE_KEY=pk_test_xxx        # pk_live_xxx in production
STRIPE_SECRET=sk_test_xxx     # sk_live_xxx in production
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

> **Saudi Arabia note:** Stripe doesn't onboard KSA-based merchant entities
> directly. Options: a Stripe-supported entity (e.g. UAE/US), or a local
> gateway — Moyasar, HyperPay or Tap all support mada/Apple Pay. The billing
> layer is gateway-abstracted (`app/Services/Billing/`), so a Moyasar driver
> can be added behind the same subscribe/webhook flow without touching the UI.

---

## 4. AI (review replies, post drafts)

Works offline by default with a deterministic generator. For production
quality set:

```dotenv
MAPX_AI_PROVIDER=openai
MAPX_AI_API_KEY=sk-xxx
MAPX_AI_MODEL=gpt-4o-mini
```

---

## 5. Background jobs on Laravel Cloud

Syncs, review ingestion, auto-replies and post publishing run on the queue.
Until you add a dedicated worker, set:

```dotenv
QUEUE_CONNECTION=sync
```

so jobs run inline with the request (slightly slower requests, but nothing
gets stuck "Pending"). When traffic grows, add a Laravel Cloud worker running
`php artisan queue:work` and switch `QUEUE_CONNECTION=database` (or Redis).

---

## Rollout order (recommended)

1. `QUEUE_CONNECTION=sync` → redeploy (fixes stuck "Pending" syncs today).
2. Stripe **test keys** → verify checkout with the test card → swap to live keys.
3. Google Cloud project + API access application (start now; approval takes days).
4. Meta app + App Review (also takes days; test in Development Mode meanwhile).
5. Set `MAPX_INTEGRATIONS_MOCK=false` once Google credentials are live.
