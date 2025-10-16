# Deployment Topology & Runtime Architecture

## High-Level Topology
```
          Internet
              │
           [Nginx]
         (web service)
              │ (private network only)
  ┌───────────┴───────────┐
  │           │           │
[app]      [queue]   [scheduler]
 PHP-FPM   queue work   cron (artisan schedule:run)
  │           │           │
  └───────
          │
       [reverb]
          │
  ┌──────┴──────┐
 [Redis]     [Managed DB]
 (cache/queue) (MySQL/PostgreSQL)
```
- **Public network** exposes only the `web` service (Nginx) on ports 80/443.
- **Private network** connects backend services (`app`, `queue`, `scheduler`, `reverb`) to Redis and the managed database. These services are not reachable from the internet.

## Runtime Services

The stack now runs directly on host machines or virtual machines managed by configuration management (Ansible, Laravel Forge, Ploi, etc.).

| Service | Recommended host process | Notes |
| --- | --- | --- |
| Web | Nginx (reverse proxy) | Terminates TLS, serves static assets, proxies PHP traffic to FPM, and exposes only ports 80/443. Harden configuration with Brotli/Gzip, strict headers, and upload limits. |
| Application | PHP-FPM 8.2 | Runs the Laravel application. Configure opcache, make `storage/` + `bootstrap/cache/` writable, and run under the `www-data` (or dedicated) user. |
| Queue worker | Supervisor service running `php artisan queue:work` | Scale horizontally. Set retries, `--max-time`, and visibility timeouts based on job duration. |
| Scheduler | Supervisor/systemd timer executing `php artisan schedule:run` every minute | Ensure only one scheduler instance runs per environment. |
| Reverb (WebSockets) | Supervisor service running `php artisan reverb:start --host=0.0.0.0` | Protect with TLS termination and load balancer health checks. |
| Redis | Managed service (preferred) or host installation | Use for queue/cache/broadcast storage; enforce authentication and TLS where available. |
| Database | Managed MySQL/MariaDB/PostgreSQL | Enable automated backups, encryption at rest, and restrictive firewall rules. |

## Environment Separation
- **Development**: Run services locally via PHP's built-in server (`php artisan serve`), Vite (`npm run dev`), and optional MySQL/Redis instances (e.g., Homebrew, Valet, WSL packages). Keep `.env` synced with `.env.example`.
- **Staging**: Provision a VM with Nginx, PHP-FPM, Redis (or managed), and point it at a managed database. Deploy via Git/CI, run migrations with `php artisan migrate --force`, and secure traffic with HTTPS.
- **Production**: Mirror staging but with auto-scaling/load-balanced web nodes if required. Use managed MySQL/Redis, enforce WAF/IPS rules, and enable observability (metrics/log aggregation).

All environment variables are supplied via `.env` (development) or environment-specific secret stores (CI/CD variables, secret managers). Required settings include `APP_ENV`, `APP_DEBUG=false` (staging/prod), `APP_URL` (HTTPS), `APP_KEY`, `DB_*`, `REDIS_*`, `MAIL_*`, `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, explicit CORS origins, and `BROADCAST_*`/`REVERB_*` (WSS in production). In production set `FILESYSTEM_DISK=s3` (or equivalent) and inject credentials via a secret manager.

### Secret Rotation
1. Provision a new secret (e.g., `APP_KEY_NEXT`) and deploy code capable of reading both secrets.
2. Switch the platform secrets to the new value.
3. Redeploy and confirm `/readyz` stays green.
4. Remove the old secret once the rollout is complete.

## Health & Readiness
- `/livez` – simple liveness check (responds if the PHP process is alive).
- `/readyz` – readiness check verifying database, Redis, and queue connectivity. Nginx or upstream load balancers should only forward traffic to PHP-FPM when this endpoint returns `200`.
- `/health` – JSON diagnostics from Spatie Health (aggregated view for operators).

## Zero-Downtime Deployments
1. CI builds the application artifacts (composer/npm) and runs the full test suite.
2. Package a release archive (excluding `node_modules`, `vendor`, cache) or rely on atomic `git pull` on the target servers.
3. Put the application in maintenance mode (`php artisan down --render=errors/maintenance`) if schema changes are disruptive.
4. Deploy code (rsync, Envoy, Deployer, Forge/Ploi git deploy), install dependencies (`composer install --no-dev --optimize-autoloader`, `npm ci && npm run build` where applicable).
5. Run `php artisan migrate --force`, clear caches (`php artisan config:cache`, `route:cache`, `view:cache`), then bring the app back up (`php artisan up`).
6. Execute smoke tests (`GET /health`, booking workflow) and monitor p95 latency / 5xx counts.

### Migrations & Schema Stability
- Always run `php artisan migrate --force` after a deployment once `/readyz` is healthy.
- Follow expand/contract: introduce additive changes first, remove legacy columns in a subsequent release.
- Limit seeding in production to reference data. Demo data belongs to staging.

## Storage & Logs
- Application logs are written to files under `storage/logs` or forwarded to stdout via systemd/journald. Ship them to your logging platform (ELK, Loki, CloudWatch) for retention.
- Uploads should be stored on S3/GCS; local storage is limited to cache directories. Ensure `storage/` is writable and included in backup routines.
- Redis data should rely on a managed service or host-level persistence; configure AOF/snapshots appropriately.

## Workers & Scheduler
- `queue`: run multiple Supervisor-managed processes; configure visibility timeout > job max duration; plan retries/dead-letter queues.
- `scheduler`: host cron (or systemd timer) executes `php artisan schedule:run` every minute—ensure only one instance per environment.
- Horizon optional for observability; secure the dashboard behind auth + IP restrictions.

## TLS & Runtime Security
- Terminate TLS at Nginx (Let’s Encrypt automation or managed certificates) and redirect HTTP→HTTPS with HSTS enabled.
- Configure CORS per environment—explicit domains for staging/production, never `*`—and ensure cookies are `Secure` with appropriate `SameSite` values.
- Run services as non-root users with least privileges; keep writable directories limited to `storage/` and `bootstrap/cache/`.
- Apply OS security updates promptly, enable fail2ban/WAF where possible, and scan dependencies via Composer/NPM audit tooling and SBOM generation.

## Backup & Recovery
- Managed database: daily encrypted snapshots (retention 7–30 days) with weekly restores tested on staging.
- Object storage: enable versioning and lifecycle policies.
- Document RPO/RTO targets (e.g., RPO 24h, RTO 1h) and keep restoration scripts in the runbook.

## Rollback Strategy
- Keep previous release archives or git tags (e.g., `vX.Y.Z-1`) to redeploy quickly (`php artisan migrate:rollback` if safe).
- Avoid destructive migrations in the same deploy as schema changes; follow expand/contract.
- For data rollback, restore the nearest snapshot and re-run the application deployment.

## Post-Deployment Smoke Tests
- `/health`, `/readyz` return `200`.
- Homepage renders without errors.
- End-to-end test creates and cancels a reservation (staging only), verifying email dispatch.
- Prometheus metrics update (`hotel_http_requests_total`, `hotel_bookings_total`).

## Implementation Timeline (Suggested)
1. Provision local tooling (PHP, Composer, Node, MySQL, Redis) and confirm migrations from scratch.
2. Automate host provisioning with infrastructure-as-code (Terraform + Ansible/Forge/Ploi) for staging and production.
3. Configure CI to run tests, package releases, and trigger deployments after approval.
4. Implement observability (Prometheus/Grafana, `/metrics`, dashboards, alerts).
5. Document production gates & rollback procedures (TLS, CORS, manual approval).
6. Schedule table-top exercises (DB outage, 5xx spike) using the runbook to validate recovery steps.

Use this document alongside [`docs/environments.md`](environments.md) and [`docs/security-hardening.md`](security-hardening.md) to brief stakeholders during audits or project reviews.
