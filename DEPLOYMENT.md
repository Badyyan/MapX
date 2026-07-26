# Deploying MapX to production

**Recommended stack:** the Laravel app on **Laravel Cloud**, with **Cloudflare**
in front (DNS + CDN + WAF) and **Cloudflare R2** as the S3-compatible storage
disk. This matches how a production SaaS like brandwizard.io is served: a
Laravel-native runtime for the app, and Cloudflare's edge for performance,
protection, and cheap object storage.

Why not Workers/Vercel: both are serverless with no PHP runtime + read-only
filesystems, so they can't run a stateful multi-tenant Laravel app with queue
workers, a scheduler and file uploads. Cloudflare is used here as the edge
layer, not the app host.

---

## 1. App host — Laravel Cloud

1. Push this repo to GitHub (already on `claude/map-management-platform-gk5lea`).
2. In [Laravel Cloud](https://cloud.laravel.com), create a project from the repo.
3. Provision, from the Cloud dashboard:
   - a **PostgreSQL** (or MySQL) database,
   - a **Redis** instance (cache + queue),
   - a **queue worker** process running `php artisan queue:work`,
   - the **scheduler** (`php artisan schedule:run` every minute) — this drives
     review fetching, scheduled-post publishing, daily rank snapshots and the
     billing lifecycle (see `routes/console.php`).
4. Set the environment variables (section 3) and deploy. The deploy runs
   `php artisan migrate --force`; run `php artisan db:seed --class=DemoSeeder`
   once if you want the demo tenant.

Build commands (Laravel Cloud auto-detects, shown for reference):

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

## 2. Edge + storage — Cloudflare

1. Add your domain to Cloudflare and point DNS at the Laravel Cloud app.
2. Enable **Full (strict)** SSL and, ideally, lock the origin so only
   Cloudflare can reach it (Authenticated Origin Pulls or an IP allowlist of
   Cloudflare's ranges). The app already trusts Cloudflare's forwarded headers
   (`bootstrap/app.php`), so audit-log IPs, HTTPS detection and secure cookies
   stay correct behind the CDN.
3. Create an **R2 bucket** for uploads and an R2 API token. R2 is
   S3-compatible, so it plugs straight into Laravel's `s3` disk via the env
   vars below — no code changes. Uploads (branch photos, post images,
   verification docs) then persist across deploys and autoscaling.

## 3. Environment variables

```dotenv
APP_NAME=MapX
APP_ENV=production
APP_DEBUG=false
APP_URL=https://app.yourdomain.com
APP_KEY=                      # php artisan key:generate --show

# Database (managed Postgres/MySQL from Laravel Cloud)
DB_CONNECTION=pgsql
DB_HOST=...
DB_PORT=5432
DB_DATABASE=mapx
DB_USERNAME=...
DB_PASSWORD=...

# Cache / queue / sessions on Redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=...
REDIS_PASSWORD=...
REDIS_PORT=6379

# Storage — Cloudflare R2 (S3-compatible)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=<r2-access-key>
AWS_SECRET_ACCESS_KEY=<r2-secret-key>
AWS_DEFAULT_REGION=auto
AWS_BUCKET=<r2-bucket-name>
AWS_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
AWS_URL=https://<your-public-r2-domain>
AWS_USE_PATH_STYLE_ENDPOINT=true

# --- MapX platform (see config/mapx.php) ---
MAPX_INTEGRATIONS_MOCK=false   # true keeps everything working without API keys
MAPX_AI_PROVIDER=openai
MAPX_AI_API_KEY=...
MAPX_BILLING_GATEWAY=auto      # auto|manual|moyasar|stripe

# Real integration credentials (optional — mock mode works without them)
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
META_APP_ID=...
META_APP_SECRET=...

# Payments — Moyasar (mada/Apple Pay) is the driver for Saudi entities
MOYASAR_PUBLISHABLE_KEY=pk_live_...
MOYASAR_SECRET_KEY=sk_live_...
MOYASAR_WEBHOOK_SECRET=...
MOYASAR_METHODS=creditcard,applepay,stcpay
```

### Payment gateway notes

- **`/webhooks/moyasar` and `/webhooks/stripe` must reach the origin.** If you
  put Cloudflare in front, exempt both from Bot Fight Mode and rate limiting —
  a blocked webhook means a customer pays and never gets activated. MapX
  already trusts the Cloudflare proxy headers.
- **The scheduler is not optional with Moyasar.** Unlike Stripe, Moyasar has no
  recurring subscriptions: MapX charges renewals itself from an hourly
  scheduled sweep. Without `schedule:run` every minute *and* a queue worker (or
  `QUEUE_CONNECTION=sync`), nothing renews and every subscription silently
  lapses at the end of its period.
- **Apple Pay needs `/.well-known/` served by the origin.** The
  `apple-developer-merchantid-domain-association` file has no extension; make
  sure neither the CDN nor a redirect rule swallows the path. Leave `applepay`
  out of `MOYASAR_METHODS` until the domain is verified.
- **Set `MOYASAR_WEBHOOK_SECRET` before going live.** The Moyasar webhook fails
  closed: with no secret configured it returns 401 outside the test suite.
  (The Stripe webhook still fails open when `STRIPE_WEBHOOK_SECRET` is unset —
  set it.)

The `public` disk isn't used in production — `FILESYSTEM_DISK=s3` sends all
uploads to R2. Public URLs come from `AWS_URL` (set an R2 public bucket domain).

## 4. Post-deploy checklist

- [ ] `APP_KEY` set, `APP_DEBUG=false`
- [ ] `php artisan migrate --force` ran clean
- [ ] Queue worker + scheduler processes are running
- [ ] An upload (branch photo) lands in R2 and renders
- [ ] `/up` health check returns 200 through Cloudflare
- [ ] Login over HTTPS sets a secure cookie; audit log shows real client IPs

---

## Troubleshooting

### `Database file at path [.../database.sqlite] does not exist`

This means `DB_CONNECTION` is still `sqlite` (the repo's local-dev default from
`.env.example`) and no Postgres database has been wired up on the host yet.
SQLite is fine for local development but must not be used in production here —
Laravel Cloud (and most container platforms) run an **ephemeral filesystem**,
so a SQLite file is wiped on every redeploy/restart and can't be shared across
scaled instances, meaning data loss.

Fix: provision the managed Postgres database from your host's dashboard, set
`DB_CONNECTION=pgsql` plus the `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/
`DB_PASSWORD` variables from section 3 above, confirm `php artisan migrate
--force` is wired into your deploy/release step (it is **not** automatic on
most platforms — check for a "release command" or "post-deploy command"
setting), then redeploy.

### 500 with no visible error

`APP_DEBUG` should stay `false` in production, but Laravel still writes the
full exception to the app's server-side logs. Check the host's log viewer
right after reproducing the error — that's the fastest way to find the actual
cause without exposing stack traces publicly.

## Alternatives

The app is a standard containerizable Laravel 12 project, so it also runs
as-is on **Fly.io**, **Railway**, **Render**, or **AWS** (ECS/Vapor + RDS +
SQS + ElastiCache + S3, per the SRS). Swap the managed services for the
platform's equivalents; the env-var contract above is the same.
