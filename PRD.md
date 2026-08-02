# MapX — Product Requirements Document (PRD)

| | |
|---|---|
| **Product** | MapX — Online Presence & Reputation Management Platform |
| **Owner** | Badyyan (mr.badyan@gmail.com) |
| **Version** | 1.0 |
| **Date** | 2026-08-02 |
| **Status** | MVP built & deployed (Laravel Cloud); real-integration rollout pending owner credentials |
| **Source docs** | `docs/BRD_SRS.docx`, `docs/BrandWizard_Profile.pdf`, this repository |

---

## 1. Product overview

### 1.1 Vision

MapX is a multi-tenant SaaS platform that lets multi-branch businesses manage
their entire online presence — map listings, reviews, customer feedback,
social posts, and local search visibility — from one dashboard, in Arabic and
English. It is a direct competitor to BrandWizard.io, purpose-built for the
Saudi market: SAR pricing, full RTL, and integrations with the platforms
Saudi customers actually use (Google Maps, Waze, Snap Map, Uber, Careem,
Bolt).

### 1.2 Problem statement

Businesses with multiple branches face:

- **Fragmented listings** — every branch must be maintained separately on
  Google, Facebook, Waze, and ride-hailing apps; data drifts out of sync and
  customers find wrong hours, phones, or locations.
- **Scattered reviews** — feedback arrives on many platforms with no single
  inbox, no SLA on responses, and no way to intercept unhappy customers
  before they post publicly.
- **No local-SEO visibility** — owners can't see where they rank on the map
  grid for the keywords that matter, or how they compare across branches.
- **Manual, error-prone posting** — publishing an offer to every branch's
  Google profile and Facebook page is copy-paste work.
- **No Arabic-first tooling** — global tools treat Arabic/RTL as an
  afterthought.

### 1.3 Positioning

| | BrandWizard.io | **MapX** |
|---|---|---|
| Market | Global (RU/EN roots) | **Saudi-first (AR/EN, RTL, SAR)** |
| Pricing | Custom/opaque | **Transparent: 99 SAR/branch/month** |
| Ride-hailing presence | — | **Uber, Careem, Bolt deep links built in** |
| Snap Map | — | **Yes (major Saudi audience)** |
| AI review replies | Yes | **Yes, bilingual AR/EN** |
| Onboarding | Manual location entry | **One-click import from Google Business Profile** |

### 1.4 Target customers

- **Primary:** Saudi SMBs and mid-market chains with 2–200 branches —
  restaurants/cafés, retail, clinics, gyms, salons, car services.
- **Secondary:** marketing agencies managing presence for multiple client
  companies (each client = one MapX company/tenant).
- **Personas:**
  - *Owner/CEO* — wants a single score: "how is my brand doing online?"
  - *Marketing manager* — runs posts, campaigns, replies, rank tracking.
  - *Branch manager* — owns one branch's data, reviews, and QR feedback.
  - *Customer-care agent* — works the reviews inbox all day.

---

## 2. Business objectives & success metrics

| Objective | KPI | Target (first 12 months) |
|---|---|---|
| Revenue | MRR | 100+ paying branches (~10k SAR MRR) by month 6 |
| Activation | Trial → paid conversion | ≥ 25 % |
| Onboarding speed | Sign-up → first branch imported | < 10 minutes (via Google import) |
| Engagement | Reviews replied within 24 h (per tenant) | ≥ 80 % |
| Reputation lift | Avg. star rating of active tenants | +0.3 within 90 days |
| Retention | Monthly logo churn | < 3 % |

---

## 3. Pricing & packaging

- **Model:** per-branch subscription. **99 SAR / branch / month**.
- **Yearly plan:** 20 % discount (950.40 SAR / branch / year).
- **Free trial:** 7 days, capped at **3 branches**, no card required.
- **Feature lock (FR-31):** when a trial expires or payment fails
  (`past_due`), the app locks all modules except Billing so the tenant can
  recover access by paying — data is never deleted.
- **Payment:** hosted card checkout with 3-D Secure verification via Stripe
  Checkout; invoices recorded per billing period with branch count.
  *KSA note:* Stripe does not onboard Saudi entities — the gateway layer is
  abstracted so a Moyasar/HyperPay/Tap driver (mada, Apple Pay) can drop in
  for local merchant-of-record. A clearly-labeled sandbox mode activates
  subscriptions without a gateway for demos/dev.

---

## 4. Scope

### 4.1 In scope (MVP — built)

1. Multi-tenant companies, branches, users, roles
2. Google Business Profile integration (OAuth, location import, sync,
   reviews, posts, insights)
3. Meta integration (Facebook Pages + Instagram Business posting)
4. Automatic deep-link presence for Waze, Snap Map, Uber, Careem, Bolt
5. Unified reviews inbox with AI-assisted replies and auto-reply rules
6. QR feedback funnels with rating-threshold routing
7. Multi-platform post publishing with per-platform status
8. Analytics dashboard (presence metrics, review KPIs, funnels)
9. Local rank tracking (keyword → map-grid position snapshots)
10. RBAC with predefined permission catalog and custom roles
11. Subscription billing (trial, per-branch pricing, Stripe, feature lock)
12. Listing verification assistance workflow
13. Audit log
14. Full EN/AR localization with RTL; light/dark themes

### 4.2 Out of scope (Phase 2+)

- WhatsApp Business inbox / messaging
- Yelp, TripAdvisor, Apple Business Connect listings
- Food-aggregator menus (HungerStation, Jahez, ToYou)
- Competitor benchmarking & share-of-voice
- Google/Meta ads management (permission `ads.manage` reserved)
- Native mobile apps (web is fully responsive)
- White-label/agency reseller portal
- Public API & webhooks for tenants

---

## 5. Functional requirements

Requirement IDs (FR-x) trace to `docs/BRD_SRS.docx`. All are implemented
unless marked otherwise.

### 5.1 Accounts & multi-tenancy (FR-1..4)

- Self-service sign-up creates a **company** (tenant), its four default
  roles, a 7-day trial subscription, and the registering user as Company
  Admin.
- Every business record carries `company_id`; a global query scope
  guarantees users only ever see their own tenant's data (enforced in code,
  covered by isolation tests).
- Users have a locale (en/ar) and an optional branch scope
  (`branch_ids`) restricting them to specific branches.
- Login, password reset, profile management.

### 5.2 Branch management (FR-5..8)

- CRUD for branches: name (EN/AR), category, address, city, geo
  coordinates, phone, website, opening hours, photos, attributes.
- **Primary onboarding path (Approach B — industry standard):** connect
  Google → MapX lists every location the user already manages in Google
  Business Profile → user selects → each becomes a branch, pre-verified,
  with a live per-branch Google connection. No manual data entry.
- Manual creation remains available for locations not on Google yet.
- Trial enforcement: creation blocked past 3 branches until subscribed.
- Data-completeness score per branch drives a "fix your listing" checklist.

### 5.3 Platform integrations (FR-9..13)

- **Google Business Profile** (primary): OAuth 2.0
  (`business.manage` scope, offline refresh tokens), account/location
  directory with pagination and read-masks, listing sync (PATCH with update
  masks), reviews fetch & reply, post publishing, performance insights.
- **Meta:** Facebook Login OAuth → long-lived token → page list with page
  tokens; user maps each Page to a branch; Instagram Business accounts
  linked to pages are auto-connected for publishing.
- **Automatic platforms:** Waze, Snap Map, Uber, Careem, Bolt have no
  listing APIs — MapX generates ready-to-use deep links per branch from its
  coordinates with zero setup, presented honestly as "no connection
  needed."
- **Honest state model:** the UI never fakes "Connected." States are:
  Connected (real tokens) / Setup required (credentials missing) /
  Demo (explicit mock-mode banner, `MAPX_INTEGRATIONS_MOCK=true`).
- Sync runs as queued jobs with per-connection status
  (pending/synced/error), last-synced time, and manual "Sync all."
- OAuth tokens are stored encrypted at rest and auto-refreshed.

### 5.4 Reviews & reputation (FR-14..18)

- **Unified inbox** aggregating reviews across platforms and branches with
  filters (platform, branch, rating, replied/unreplied, sentiment).
- Reply from MapX; replies push to the source platform (Google v4 reply
  API).
- **AI-assisted replies:** one-click suggested reply matching the review's
  language (AR/EN), tone-aware, editable before send. Provider-pluggable
  (OpenAI/Anthropic) with a deterministic offline fallback so the feature
  always works.
- **Auto-reply rules:** per rating-band and keyword conditions →
  template or AI reply, with per-branch targeting and a kill switch.
  Covered by engine tests.
- Review KPIs: volume, average rating, response rate, response time.

### 5.5 QR feedback funnels (FR-19..22)

- Campaigns generate a QR code + short public URL (`/f/{slug}`) per
  branch/campaign.
- **Threshold routing:** rating ≥ threshold → forward the customer to the
  public review platform (Google) to post publicly; rating < threshold →
  capture private feedback (comment + contact) for service recovery, never
  published.
- Private feedback appears in a dedicated inbox with resolution tracking.
- Funnel analytics: scans → ratings → public-review conversions.

### 5.6 Posts (FR-23..24)

- Compose once, publish to selected branches × platforms (Google posts,
  Facebook Pages, Instagram Business).
- Media attachment, scheduling, and per-platform delivery status
  (draft/scheduled/published/failed) with error surfacing.
- AI draft assistance for post copy (same provider abstraction).

### 5.7 Analytics & local rank (FR-25..27)

- Dashboard: presence metrics time-series (views, searches, actions —
  calls/directions/website), review KPIs, QR funnel stats, per-branch
  comparison. Interactive charts, theme-aware.
- **Local rank tracker:** tenant-defined keywords per branch; scheduled
  snapshots record map-pack position; trend charts and best/worst movers.

### 5.8 Users, roles & permissions (FR-32..35)

- Predefined 19-permission catalog (view/manage split per module).
- Four system role templates cloned into every new company: **Company
  Admin** (all), **Branch Manager**, **Review Agent**, **Viewer**.
- Custom roles: any permission combination, per company.
- Route-level enforcement via `perm:` middleware; UI hides what the user
  can't do. Cross-tenant role leakage covered by regression tests.

### 5.9 Billing (FR-28..31)

- Plans: monthly / yearly (20 % off), quantity = branch count.
- Stripe hosted Checkout (card + 3DS); webhooks (signature-verified) drive
  the local state machine:
  `checkout.session.completed` → activate; `invoice.paid` → record invoice
  idempotently + extend period; `invoice.payment_failed` → `past_due`
  (feature lock); `customer.subscription.deleted` → canceled.
- Invoice history in-app (number, amount, branch count, period, status).
- Sandbox activation path when no gateway is configured, explicitly
  labeled.

### 5.10 Verification assistance (FR-36)

- Guided workflow to get unverified listings verified on Google:
  request → track method (postcard/phone/email) → status; support notes
  per request.

### 5.11 Audit log (FR-37)

- Immutable per-tenant trail of sensitive actions (auth, role changes,
  integration connects/disconnects, billing events) with actor, IP, and
  timestamp; viewable by permission holders.

### 5.12 Localization & theming

- Full English + Arabic (447 translated strings), `dir="rtl"` layout using
  CSS logical properties throughout.
- Apple-inspired design system: token-based palette, SF-Pro-first stack,
  Lucide SVG icons (no emojis), light + dark themes with pre-paint
  detection, frosted-glass surfaces, reduced-motion support.

---

## 6. Non-functional requirements

| Area | Requirement |
|---|---|
| **Performance** | Core pages < 500 ms server time at MVP scale; long work (syncs, review fetches, rank snapshots) in queued jobs, never in the request path. |
| **Security** | Tenant isolation via global scopes; RBAC on every route; OAuth/API tokens encrypted at rest; Stripe webhooks signature-verified; CSRF everywhere except signed webhooks; hashed passwords; audit trail. Secrets only in env vars — never in the repo. |
| **Availability** | Managed platform (Laravel Cloud) with push-to-deploy and rollbacks; target 99.5 % for MVP. |
| **Scalability** | Stateless app tier; MySQL 8.4 managed; horizontal queue workers; per-tenant data partitioned by `company_id` indexes. |
| **Localization** | All user-facing strings translatable; new features must ship EN+AR together. |
| **Accessibility** | Keyboard-focus visible, ARIA labels on icon-only controls, WCAG-conscious contrast in both themes, `prefers-reduced-motion` honored. |
| **Compatibility** | Responsive from 360 px mobile to desktop; evergreen browsers. |
| **Quality** | Automated feature-test suite (currently 32 tests / 101 assertions) must pass before deploy; schema portable across SQLite (dev), Postgres, MySQL (prod). |

---

## 7. Technical architecture (summary)

- **Stack:** Laravel 13 (PHP 8.4), Blade + Tailwind CSS 4 + Vite, Chart.js;
  MySQL 8.4 in production, SQLite in dev/test.
- **Multi-tenancy:** single database, shared schema, `company_id` scoping
  via a reusable model trait (global scope + auto-fill).
- **Integration layer:** `PlatformAdapter` interface per platform,
  resolved by a `PlatformManager`; a single env switch
  (`MAPX_INTEGRATIONS_MOCK`) toggles simulated vs. real API behavior so the
  whole product is demoable without credentials.
- **Billing layer:** gateway-abstracted (`StripeGateway` today; Moyasar/
  HyperPay/Tap drivers slot in) + webhook controller.
- **AI layer:** provider-pluggable service with offline deterministic
  fallback.
- **Hosting:** Laravel Cloud (project "mapx", push-to-deploy from the
  working branch, managed MySQL attached, `php artisan migrate --force` on
  deploy). Recommended edge: Cloudflare DNS/CDN/WAF + R2 for media (see
  `DEPLOYMENT.md`).

---

## 8. Launch plan & external dependencies

| # | Dependency | Owner | Lead time |
|---|---|---|---|
| 1 | `QUEUE_CONNECTION=sync` (or a worker) on Laravel Cloud | Owner | minutes |
| 2 | Google Cloud project + **Business Profile API access application** | Owner | days–weeks (Google approval) |
| 3 | Meta developer app + **App Review** for page/IG scopes | Owner | days–weeks |
| 4 | Payment gateway account (Stripe test→live, or Moyasar/HyperPay/Tap for KSA) + webhook endpoint | Owner | days |
| 5 | Set credentials in env, then `MAPX_INTEGRATIONS_MOCK=false` | Owner | minutes |
| 6 | Custom domain + Cloudflare in front | Owner | hours |

**Rollout order:** queue → billing (test mode) → Google → Meta → mock off →
live billing keys → custom domain. Exact steps: `INTEGRATIONS.md`.

## 9. Risks & mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| GBP API access denied/delayed | Core onboarding blocked | Apply early; manual branch entry works meanwhile; demo mode for sales |
| Stripe unavailable for KSA entity | Can't collect revenue | Abstracted gateway; build Moyasar driver (mada + Apple Pay) — next planned work |
| Meta App Review rejection | No FB/IG posting | Scopes requested are standard; Google-only launch is viable |
| API quota limits (GBP) | Sync lag at scale | Queued jobs with backoff; per-tenant sync scheduling |
| Rank-tracking data source ToS | Legal exposure | Use official Performance API signals first; scraping only via compliant third-party data providers |

## 10. Open questions

1. Local gateway choice: Moyasar vs. HyperPay vs. Tap (recommendation:
   **Moyasar** — simplest API, mada + Apple Pay, Saudi-native).
2. VAT handling (15 % KSA) — display-inclusive vs. added at checkout; ZATCA
   e-invoicing compliance timeline.
3. Agency/multi-company accounts — Phase 2 packaging and pricing.
4. Data residency — is KSA hosting (e.g., a Saudi region) a sales
   requirement for government/enterprise deals?

---

*Related documents: `README.md` (architecture & setup), `INTEGRATIONS.md`
(credential setup), `DEPLOYMENT.md` (hosting), `PROJECT_STATUS.md`
(current state).*
