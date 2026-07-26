# MapX — Project Status (handoff)

_Last updated: 2026-07-04. Read this first when picking up the project in a
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
- Tests: 32 passing (101 assertions). `php artisan test`.

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
2. Stripe account + test keys → verify checkout → live keys. KSA note:
   Stripe doesn't onboard Saudi entities; Moyasar/HyperPay/Tap are the local
   alternatives (gateway layer is abstracted for a drop-in driver).
3. Google Cloud project + Business Profile API access application
   (approval takes days/weeks) → GOOGLE_CLIENT_ID/SECRET.
4. Meta developer app + App Review → META_APP_ID/SECRET.
5. Then set MAPX_INTEGRATIONS_MOCK=false.

## Possible next work

- Moyasar (mada/Apple Pay) gateway driver — offered, not yet requested.
- Open a PR / merge strategy once owner is ready.
- Cloudflare in front (DNS/CDN/WAF + R2 uploads) per DEPLOYMENT.md.

## Key docs

- `README.md` — architecture + local setup
- `DEPLOYMENT.md` — hosting guide + troubleshooting
- `INTEGRATIONS.md` — Google/Meta/Stripe/queue setup, rollout order
