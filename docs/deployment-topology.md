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

## Container Images

| Image | Description | Build notes |
| --- | --- | --- |
| `docker/app/Dockerfile` | PHP-FPM runtime for Laravel | Multi-stage build. Stage 1 installs Composer dependencies without dev packages; stage 2 compiles Vite assets; final stage is a slim PHP-FPM Alpine image with Opcache enabled and only the required PHP extensions. Filesystem runs as non-root (`www-data`), read-only except for `storage/` and `bootstrap/cache/`. |
| `docker/nginx/Dockerfile` | Nginx reverse proxy | Hardened config with TLS support, Brotli/GZip compression, strict security headers, static caching, and upload limits. Only ports 80/443 are exposed. |
| `queue` / `scheduler` / `reverb` | Reuse PHP-FPM image | Same runtime image as `app`, different entrypoints (`queue:work`, `schedule:work`, `reverb:start`). Restart policies ensure resilience. |

### Build Rules
- PIN base images (`php:8.2-fpm-alpine3.20`, `nginx:1.27-alpine`, `redis:7.2-alpine`) to avoid `latest` drifts.
- Clear build caches and exclude secrets using `.dockerignore`.
- CI must run Trivy scans and generate an SBOM for every image before pushing to the registry.

## Environment Separation
- **Development (`docker-compose.dev.yml`)** mounts the source tree, exposes MySQL/Redis/Mailhog locally, and runs queue/scheduler/reverb services.
- **Staging (`deploy/compose.staging.yml`)** mirrors production (read-only filesystem, healthchecks, TLS), but can reuse Docker volumes and self-managed Redis if a cloud service is not available.
- **Production (`deploy/compose.prod.yml`)** expects images published to a registry (`ghcr.io/your-org/...`). Database and SMTP should be managed services; Redis can be managed or self-hosted with persistence.

All environment variables are supplied via `.env` (development) or environment-specific secret files (`staging.env`, `prod.env`) that are **not** committed. Required settings include `APP_ENV`, `APP_DEBUG=false` (staging/prod), `APP_URL` (HTTPS), `APP_KEY`, `DB_*`, `REDIS_*`, `MAIL_*`, `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN`, `SANCTUM_STATEFUL_DOMAINS`, explicit CORS origins, and `BROADCAST_*`/`REVERB_*` (WSS in production). In production set `FILESYSTEM_DISK=s3` (or equivalent) and inject credentials via a secret manager.

### Secret Rotation
1. Provision a new secret (e.g., `APP_KEY_NEXT`) and deploy code capable of reading both secrets.
2. Switch the platform secrets to the new value.
3. Redeploy and confirm `/readyz` stays green.
4. Remove the old secret once the rollout is complete.

## Health & Readiness
- `/livez` – simple liveness check (responds if the PHP process is alive).
- `/readyz` – readiness check verifying database, Redis, and queue connectivity. Nginx and orchestrators should only send traffic to the `app` container when this endpoint returns `200`.
- `/health` – JSON diagnostics from Spatie Health (aggregated view for operators).

### Docker Healthchecks
Each compose file wires container healthchecks to the liveness/readiness endpoints. If a healthcheck fails repeatedly, the orchestrator restarts the container.

## Zero-Downtime Deployments
1. CI builds and tags the images (`app`, `web`) with `vX.Y.Z` and the commit SHA.
2. Images are pushed to the registry and referenced by the staging compose file.
3. On staging deployment: `docker compose pull`, `docker compose up -d --remove-orphans`, run `php artisan migrate --force`, and wait for `/readyz` to return `200`.
4. Execute smoke tests (`GET /health`, booking workflow) and run ZAP Baseline. If all checks pass, approve production deployment.
5. Production deployment mirrors staging with manual approval. Monitor p95 latency and 5xx counts for several minutes.

### Migrations & Schema Stability
- Always run `php artisan migrate --force` after a deployment once `/readyz` is healthy.
- Follow expand/contract: introduce additive changes first, remove legacy columns in a subsequent release.
- Limit seeding in production to reference data. Demo data belongs to staging.

## Storage & Logs
- Application logs are written to stdout/stderr (JSON) and collected by the platform.
- Uploads should be stored on S3/GCS; local storage volumes are mounted only for cache directories.
- Redis data persists via named volumes (development/staging) or a managed service.

## Workers & Scheduler
- `queue`: scale horizontally; configure visibility timeout > job max duration; plan retries/dead-letter queues.
- `scheduler`: dedicated container running `schedule:work` (or host cron calling `schedule:run` every minute).
- Horizon optional for observability (protect access).

## TLS & Runtime Security
- Terminate TLS at Nginx (Let’s Encrypt automation or managed certificates) and redirect HTTP→HTTPS with HSTS enabled.
- Configure CORS per environment—explicit domains for staging/production, never `*`—and ensure cookies are `Secure` with appropriate `SameSite` values.
- Run containers as non-root with minimal Linux capabilities; keep filesystems read-only apart from `storage/` and `bootstrap/cache/`.
- Scan every image with Trivy and block releases on unresolved HIGH/CRITICAL issues; generate CycloneDX/Syft SBOMs for traceability.

## Backup & Recovery
- Managed database: daily encrypted snapshots (retention 7–30 days) with weekly restores tested on staging.
- Object storage: enable versioning and lifecycle policies.
- Document RPO/RTO targets (e.g., RPO 24h, RTO 1h) and keep restoration scripts in the runbook.

## Rollback Strategy
- Keep previous image tags (e.g., `vX.Y.Z-1`) to redeploy quickly.
- Avoid destructive migrations in the same deploy as schema changes; follow expand/contract.
- For data rollback, restore the nearest snapshot and re-run the application deployment.

## Post-Deployment Smoke Tests
- `/health`, `/readyz` return `200`.
- Homepage renders without errors.
- End-to-end test creates and cancels a reservation (staging only), verifying email dispatch.
- Prometheus metrics update (`hotel_http_requests_total`, `hotel_bookings_total`).

## Implementation Timeline (Suggested)
1. Local Sail/docker-compose setup (DB/Redis/Mailhog) with migrations from scratch.
2. Build optimized PHP-FPM and Nginx images in CI; push to registry.
3. Staging compose with secrets, healthchecks, automated deployment + smoke tests.
4. Observability (Prometheus/Grafana, `/metrics`, dashboards, alerts).
5. Production gate & rollback documentation (TLS, CORS, manual approval).
6. Image scanning (Trivy) & SBOM enforcement in CI.
7. Table-top exercise (DB outage, 5xx spike) using the runbook.

Use this document alongside [`docs/environments.md`](environments.md) and [`docs/security-hardening.md`](security-hardening.md) to brief stakeholders during audits or project reviews.
