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
MAPX_BILLING_GATEWAY=hyperpay  # or stripe

# Real integration credentials (optional — mock mode works without them)
GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
META_APP_ID=...
META_APP_SECRET=...
```

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

## Alternatives

The app is a standard containerizable Laravel 12 project, so it also runs
as-is on **Fly.io**, **Railway**, **Render**, or **AWS** (ECS/Vapor + RDS +
SQS + ElastiCache + S3, per the SRS). Swap the managed services for the
platform's equivalents; the env-var contract above is the same.
