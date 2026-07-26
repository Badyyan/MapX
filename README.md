# MapX — Online Presence & Reputation Management Platform

MapX is a multi-tenant SaaS platform (a BrandWizard-style product, built to the
BrandPresence360 BRD/SRS) that lets businesses in Saudi Arabia manage their
locations, listings, reviews, reputation and local search rankings across maps,
social networks and ride services — from a single dashboard, in **English and
Arabic (full RTL)**.

> **Demo login:** `demo@mapx.app` / `password` (seeded via `php artisan db:seed`)
> Extra seeded users: `manager@mapx.app`, `agent@mapx.app` (same password).

---

## Feature map (BRD → implementation)

| BRD module | Where it lives |
|---|---|
| Company & branch management, CSV import | `BranchController`, `branches/*` views |
| Platform integrations (GBP, Facebook, Instagram, Waze, Snap, Uber/Careem/Bolt) | `app/Services/Platforms/*`, `IntegrationController` |
| Unified reviews inbox + filters | `ReviewController@index` |
| AI reply suggestions & post drafts (FR-15/22) | `app/Services/AiService.php` |
| Sentiment analysis + topic extraction (FR-13) | `app/Services/SentimentClassifier.php` |
| Auto-reply rules engine (FR-16/17) | `app/Services/AutoReplyEngine.php`, `AutoReplyRuleController` |
| QR review & feedback flow (FR-18..20) | `QrCampaignController`, `FeedbackController` (public `/f/{slug}`) |
| Posts & scheduling (FR-21..24) | `PostController`, `app/Jobs/PublishPost.php` |
| Analytics: health score, trends, actions, QR funnel (FR-25) | `app/Services/AnalyticsService.php` |
| Local rank tracker (FR-26) | `app/Services/RankTracker.php`, `RankTrackerController` |
| RBAC: custom roles + predefined permission catalog (FR-2/3) | `config/mapx.php`, `Role` model, `perm:` middleware |
| Billing: 99 SAR/branch/mo, 20% yearly discount, 7-day trial, feature lock (FR-28..31) | `app/Services/BillingService.php`, `subscription` middleware |
| Payments: mada/Apple Pay (Moyasar) + Stripe, behind one driver contract | `app/Services/Billing/*`, `BillingController`, `*WebhookController` |
| Verification assistance (FR-32..34) | `VerificationController` |
| Audit logs | `AuditLog::record()` |
| EN/AR + RTL | `lang/ar.json`, `SetLocale` middleware, logical CSS properties |
| Multi-tenancy | `BelongsToCompany` trait (global scope per company) |

## Tech stack

- **Backend:** Laravel 13 (PHP 8.4), service-layer architecture per the SRS
- **DB:** SQLite out of the box; MySQL/RDS-ready (set `DB_CONNECTION=mysql`)
- **Queue:** `database` driver locally; swap to SQS in production (SRS 4.3)
- **Frontend:** Blade + Tailwind CSS 4 (Vite) + Chart.js
- **QR codes:** `bacon/bacon-qr-code` (SVG, no external service)
- **AI:** any OpenAI-compatible API; deterministic offline fallback built in

## Quick start

```bash
composer install
npm install && npm run build

cp .env.example .env
php artisan key:generate

php artisan migrate --seed     # seeds the full demo tenant
php artisan storage:link
php artisan serve
```

Open http://localhost:8000 and log in with `demo@mapx.app` / `password`,
or register a new company (starts a 7-day trial, max 3 branches).

For background jobs and the scheduler in development:

```bash
php artisan queue:work
php artisan schedule:work
```

## Mock mode vs real integrations

Everything works out of the box with `MAPX_INTEGRATIONS_MOCK=true`: platform
syncs, review replies and post publishing succeed against simulated APIs so the
entire product can be exercised (and demoed) without credentials.

To go live:

1. **Google Business Profile** — create a Google Cloud project, enable the
   Business Profile APIs, request API access from Google, set
   `GOOGLE_CLIENT_ID`/`GOOGLE_CLIENT_SECRET`, and implement the OAuth redirect
   in `IntegrationController@connect` (the token fields on
   `platform_connections` are already encrypted at rest, per SRS 4.2).
2. **Meta (Facebook/Instagram)** — create a Meta app with `pages_manage_posts`,
   `instagram_content_publish`, set `META_APP_ID`/`META_APP_SECRET`.
3. **AI** — set `MAPX_AI_PROVIDER=openai` and `MAPX_AI_API_KEY` (any
   OpenAI-compatible endpoint works via `MAPX_AI_BASE_URL`).
4. **Rank tracking** — plug a SERP provider (DataForSEO, SerpApi, …) into
   `RankTracker::fetchPosition()`.
5. **Billing** — `MAPX_BILLING_GATEWAY=auto|manual|moyasar|stripe`. Moyasar
   (mada/Apple Pay) is the driver for Saudi merchants; Stripe needs a
   non-KSA entity. Set the driver's keys and webhook URL and the rest is
   automatic — see INTEGRATIONS.md.
6. Set `MAPX_INTEGRATIONS_MOCK=false`.

Waze, Snap Map, Uber, Careem and Bolt need no credentials — per the BRD they
are deep-link/visibility integrations (these platforms do not allow listing
edits via API).

## Architecture notes

- **Multi-tenancy** — every tenant-owned model uses the `BelongsToCompany`
  trait: a global scope filters all queries by the authenticated user's
  `company_id` and fills it on create. Jobs and public flows explicitly use
  `withoutGlobalScope('company')`.
- **RBAC** — permissions are a fixed catalog in `config/mapx.php`; roles store
  a JSON subset. Four system roles (Admin, Branch Manager, Review Agent,
  Viewer) are cloned into every new company; admins can create custom roles.
  Routes are guarded with `perm:<permission>` middleware.
- **Subscription gate** — the `subscription` middleware locks every feature
  route when the trial expires or payment fails, while keeping `/billing`
  reachable (FR-31).
- **Platform adapters** — each integration implements `PlatformAdapter`
  (sync, reviews, replies, posts, insights). Mock behaviour lives in
  `BaseAdapter`; real HTTP calls live in each concrete adapter.
- **Payment drivers** — the same shape for billing: each gateway implements
  `PaymentGateway` (`key`, `isConfigured`, `checkoutUrl`, `confirm`, `cancel`)
  and inherits the shared, tenancy-correct state machine from `BaseGateway`
  (idempotent invoice recording, period extension, past-due/cancel). Webhook
  payloads stay inside the driver that understands them.
  `PaymentGatewayManager` resolves `MAPX_BILLING_GATEWAY`, degrading to the
  sandbox `ManualGateway` when the named driver has no credentials. Moyasar
  additionally drives its own renewals — it has no subscription primitive, so
  MapX charges the saved card token from the scheduler.
- **Queues & scheduler** — syncs, review ingestion, auto-replies, post
  publishing and rank snapshots all run as queued jobs. `routes/console.php`
  schedules: daily review fetch (03:00), scheduled-post release (every
  minute), daily rank snapshots (04:00), hourly billing lifecycle and hourly
  Moyasar renewal sweep.

## Testing

```bash
php artisan test
```

53 feature tests cover registration/provisioning, branch CRUD + trial limits +
tenant isolation, RBAC enforcement, the QR threshold flow, the auto-reply
engine, billing math/locking, gateway driver resolution, and both payment
gateways end to end (Stripe webhooks; Moyasar checkout, amount verification,
webhooks and renewal dunning — all via `Http::fake`, no account needed).

## Deployment (SRS §9)

- Any Laravel-capable host works. For AWS per the SRS: ECS (or Vapor),
  RDS MySQL, SQS (`QUEUE_CONNECTION=sqs`), ElastiCache Redis
  (`CACHE_STORE=redis`), S3 for media (`FILESYSTEM_DISK=s3`).
- Run `php artisan migrate --force` on deploy, a `queue:work` worker service
  (auto-scaled), and `schedule:run` via cron/EventBridge every minute.
- Build assets in CI with `npm ci && npm run build`.

## Project documents

The original BrandWizard profile and the BRD/SRS this build follows are in
`docs/`.
