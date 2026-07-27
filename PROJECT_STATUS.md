# MapX — Project Status (handoff)

_Last updated: 2026-07-26. Read this first when picking up the project in a
new session._

## What MapX is

Multi-tenant SaaS for online presence & reputation management (BrandWizard
competitor for the Saudi market), built per `docs/BRD_SRS.docx`. Laravel 13 +
Blade + Tailwind 4, Apple-inspired design system with dark mode, full EN/AR +
RTL. Owner: Badyyan (mr.badyan@gmail.com). Working branch:
`claude/map-management-platform-gk5lea` (never merged to a default branch yet;
no PR opened — owner hasn't asked to merge).

## Current state — DONE

- All BRD modules: branches, integrations, reviews inbox + AI replies,
  auto-reply rules, QR review funnels, posts, analytics, local rank tracker,
  RBAC, billing, verification assistance, audit log, demo seeder
  (demo@mapx.app / password — local/staging only, not seeded in prod).
- Premium UI: design system in `resources/css/app.css`, Lucide icon component,
  Apple palette, dark mode toggle, frosted sidebar/topbar, zero overflow
  (automated audit), 447 Arabic strings.
- **Real integrations (latest work):**
  - Google Business Profile OAuth + location import (approach B: connect →
    list all GBP locations → import as branches). `app/Services/Google/*`,
    `GoogleIntegrationController`.
  - Meta OAuth + page→branch mapping (`app/Services/Meta/*`).
  - Stripe Checkout + signature-verified webhooks
    (`app/Services/Billing/StripeGateway.php`, `/webhooks/stripe`).
  - Honest UI states: demo-mode banner, "Setup required", automatic
    deep-link platforms (Waze/Uber/Careem/Bolt/Snap need no connection).
- **Payments abstraction + Moyasar (latest work):** the billing layer is now
  genuinely gateway-abstracted — `PaymentGateway` contract, `BaseGateway`
  (idempotent invoices, tenancy-safe writes, period/past-due transitions),
  `PaymentGatewayManager` resolving `MAPX_BILLING_GATEWAY`
  (`auto|manual|moyasar|stripe`, degrading to sandbox when keys are missing).
  Moyasar driver ships: MapX-branded embedded checkout (`/billing/checkout`)
  for mada/Apple Pay/STC Pay, server-side amount re-verification (the embedded
  form's amount is client-editable), fail-closed webhook at `/webhooks/moyasar`,
  and MapX-driven renewals from an encrypted saved card token with a 3×24 h
  dunning ladder that fits inside the existing 3-day grace window.
- Tests: 54 passing (174 assertions). `php artisan test`.

## Production deployment (Laravel Cloud)

- App: project "mapx", env tied to this branch, push-to-deploy ON.
  URL: mapx-claude-map-management-platform-gk5lea-jh03yz.laravel.cloud
- Database: Laravel MySQL 8.4 ("mapx-db", Dev tier) attached; DB_* vars
  auto-injected. Deploy command `php artisan migrate --force` is set
  (was once commented out with `#` — that caused the first 500s).
- Owner registered a real admin (wanted.hf@gmail.com) — registration works.

## PENDING — owner's side (see INTEGRATIONS.md for exact steps)

1. Add `QUEUE_CONNECTION=sync` env var on Laravel Cloud (jobs currently
   have no worker → syncs stick at "Pending").
2. **Moyasar account** (this is the one that lets you actually charge Saudi
   merchants) → test keys → complete a test payment → live keys. Set
   `MOYASAR_PUBLISHABLE_KEY`, `MOYASAR_SECRET_KEY`, `MOYASAR_WEBHOOK_SECRET`
   (webhook: `https://YOUR-DOMAIN/webhooks/moyasar`, events `payment_paid` +
   `payment_failed`). Apple Pay additionally needs the domain-association file
   at `/.well-known/` and domain validation in the Moyasar dashboard — until
   then drop `applepay` from `MOYASAR_METHODS`.
   Stripe stays available but only works with a non-KSA entity.
3. Google Cloud project + Business Profile API access application
   (approval takes days/weeks) → GOOGLE_CLIENT_ID/SECRET.
4. Meta developer app + App Review → META_APP_ID/SECRET.
5. Then set MAPX_INTEGRATIONS_MOCK=false.

## Possible next work

- **Verify the Moyasar seams against live docs.** `docs.moyasar.com` is
  unreachable from this environment (proxy 403), so three details were built
  from official-doc search results + third-party SDKs and should be confirmed
  before taking live payments: (a) the saved-card token field on the payment
  response — `MoyasarGateway::savedCardToken()` accepts both spellings it
  found, and renewals do nothing if neither is present; (b) the embedded
  form's save-card option name (`credit_card.save_card` in
  `billing/checkout.blade.php`); (c) whether merchant-initiated token charges
  can skip 3-D Secure — if they can't, unattended renewals aren't possible and
  the flow has to email the customer a confirmation link instead.
- Re-check the `moyasar-payment-form` CDN version (`MOYASAR_FORM_VERSION`,
  currently 2.2.10) when Moyasar ships a new form.
- Decide whether a Moyasar refund should also cancel the subscription. It
  currently marks the invoice Refunded and audit-logs it, deliberately
  leaving the subscription alone — that's a product call, not a bug.
- Open a PR / merge strategy once owner is ready (there is still no default
  branch in the repo).
- Cloudflare in front (DNS/CDN/WAF + R2 uploads) per DEPLOYMENT.md.

## Key docs

- `README.md` — architecture + local setup
- `DEPLOYMENT.md` — hosting guide + troubleshooting
- `INTEGRATIONS.md` — Google/Meta/Stripe/queue setup, rollout order
