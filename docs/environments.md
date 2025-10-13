# Environment & Secrets Management

This guide explains how configuration is split across development, staging, and production, and how we keep secrets out of version control.

## Environment Matrix
| Environment | Branch Source | `APP_ENV` | `APP_DEBUG` | Primary URL | Purpose |
| --- | --- | --- | --- | --- | --- |
| Local development | Feature branches, `develop` | `local` | `true` | `http://localhost:8000` | Run code, experiment rapidly, verbose logging allowed. |
| Automated tests | CI jobs | `testing` | `true` | `http://localhost` | Deterministic suite with SQLite and log mail driver. |
| Staging | Release candidates on `develop` | `staging` | `false` | `https://staging.example.com` | Dress rehearsal for production with identical stack and security posture. |
| Production | `main` | `production` | `false` | `https://hotel.example.com` | Customer-facing deployment with full observability and backups. |

## Configuration Sources
- `.env.example` – versioned template that lists every supported variable with safe placeholder values. Update this file whenever a new config key is introduced.
- `.env` – **not versioned**; used only on local machines (based on `.env.example`).
- `.env.testing` – versioned defaults for the automated test environment (SQLite, log mailer, array sessions). GitHub Actions and local developers can reuse it.
- Staging / production – secrets provided by infrastructure (GitHub Actions environments, Kubernetes/Docker secrets, or server-side `.env` files) and never committed.

## Environment Profiles
### Local development (`APP_ENV=local`)
- `APP_DEBUG=true`, `LOG_LEVEL=debug`, `LOG_CHANNEL=stack`.
- Database credentials point to your local MySQL instance.
- `CACHE_DRIVER=file`, `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=file`.
- CORS and Sanctum allow localhost origins (`CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`), cookies are non-secure (`SESSION_SECURE_COOKIE=false`).
- Mail is routed to Mailhog or another test SMTP server.
- Reverb runs over plain HTTP on `127.0.0.1:6001`.

### Automated tests (`APP_ENV=testing`)
- Defined in `.env.testing`; uses `DB_CONNECTION=sqlite` with `:memory:` to avoid side effects.
- `MAIL_MAILER=log`, `BROADCAST_DRIVER=log`, `FILESYSTEM_DISK=local`.
- `CACHE_DRIVER=array`, `SESSION_DRIVER=array`, `QUEUE_CONNECTION=sync` keep the suite fast.
- Pipelines load this file and never require secrets.

### Staging (`APP_ENV=staging`)
- Matches production infrastructure, but isolated data.
- `APP_DEBUG=false`, `LOG_LEVEL=info`, alerts routed to Slack/SIEM.
- HTTPS enforced (`APP_URL`, `FRONTEND_URL` use `https://`), `SESSION_SECURE_COOKIE=true`, `SESSION_DOMAIN=.staging.example.com`, strict `SANCTUM_STATEFUL_DOMAINS`.
- `TRUSTED_PROXIES=*` (or specific CIDRs) behind the load balancer; configure `TrustProxies` middleware accordingly.
- `CORS_ALLOWED_ORIGINS` limited to staging SPA domains.
- Queue workers, cache, Reverb, and file storage are configured exactly like production to avoid surprises.

### Production (`APP_ENV=production`)
- Mirrors staging but with real data and monitoring integrations enabled.
- `LOG_LEVEL=info` (or `warning`) and log shipping to centralized tooling.
- Backups, alerting, and incident automation enabled; see the [runbook](runbook.md).
- Database credentials grant the minimum privileges required—no SUPER/FILE access.

## GitHub Branch Protection
- `main`: require pull requests, 2 approvals, branches up to date, restrict pushes (include administrators).
- `develop`: require pull requests, ≥1 approval, up-to-date branch requirement mirrors `main`.
- Short-lived branches (`feat/*`, `fix/*`, `chore/*`, `docs/*`) branch from `develop` and merge back via PRs.

## Secrets in GitHub Actions
Set secrets under **Settings → Secrets and variables → Actions** and scope them to the staging/production environments:
- `APP_KEY_STAGING`, `APP_KEY_PRODUCTION`
- Database credentials (`DB_HOST_*`, `DB_DATABASE_*`, `DB_USERNAME_*`, `DB_PASSWORD_*`)
- Mail/SMS providers (`MAIL_*`, etc.)
- Reverb/Pusher credentials (`REVERB_APP_ID_*`, `REVERB_APP_KEY_*`, `REVERB_APP_SECRET_*`)
- Storage credentials (`AWS_*`), third-party API keys.

Never echo secrets in logs or persist them in artefacts. Enable secret scanning (Dependabot, GitHub Advanced Security, or [gitleaks](https://github.com/gitleaks/gitleaks)) in CI to block accidental commits.

## GitHub Environments & Deployments
- Create **staging** and **production** environments under **Settings → Environments**.
- Attach the corresponding secrets and enable *Required reviewers* for deployments.
- Build/test jobs run without secrets (using `.env.testing`).
- Deploy jobs target the appropriate environment, pull scoped secrets, and push configuration to the server/cluster.

## Server-Side Secret Storage
Two patterns are supported:
1. **Encrypted `.env` files** deployed outside the repo (permissions `600`, owned by the PHP-FPM user).
2. **Orchestrator secrets** (Docker/Kubernetes, systemd EnvironmentFiles, cloud secret stores).

Document each change (who/when/what) and rotate credentials regularly:
1. Add the new secret.
2. Deploy and confirm the application uses it.
3. Revoke the old secret.

## `APP_KEY` Lifecycle
- Generate a unique key per environment (`php artisan key:generate --show`).
- Local developers run `php artisan key:generate` after copying `.env.example`.
- Staging/production keys live in GitHub Action secrets (or server secret stores) and should not change between deployments. Rotating a key invalidates encrypted data and sessions—plan downtime or a migration window if rotation is required.

## CORS, Cookies & Proxy Controls
- Development: allow localhost origins, keep cookies insecure, and optionally disable Sanctum stateful domains when working on APIs only.
- Staging/production: enumerate allowed origins (`https://staging.example.com`, `https://www.example.com`), enforce `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=strict|lax` depending on flows, and configure `TRUSTED_PROXIES` to match your edge network.
- Audit middleware headers (HSTS, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, CSP) with every release.

## CI/Test Expectations
- Pipelines run `composer lint`, `composer analyse`, `composer test`, `composer audit`, `npm run check`, and `npm audit --omit=dev`.
- `php artisan migrate:fresh --seed` must succeed against the test database before merging.
- Keep Dependabot enabled for Composer, npm, and GitHub Actions workflows.

See [`docs/project-management.md`](project-management.md) for labelling, milestones, and project workflows, and [`docs/ci-security.md`](ci-security.md) for security scanning details.

## Quick Checklists
### Initial Setup (one-time)
- [ ] `.env.example` up to date with every variable (no secrets).
- [ ] Local `.env` created from the template and excluded from git.
- [ ] `.env.testing` committed; test suite passes on a clean checkout.
- [ ] GitHub Actions secrets created per environment (staging, production).
- [ ] GitHub environments `staging` and `production` protected with required reviewers.
- [ ] Servers store secrets outside the repo (encrypted `.env` or orchestrator secrets).

### Environment Health
**Development**
- [ ] `APP_DEBUG=true`, verbose logs allowed.
- [ ] CORS allows localhost origins (`CORS_ALLOWED_ORIGINS`, `SANCTUM_STATEFUL_DOMAINS`).
- [ ] Mail routed to Mailhog or test SMTP.
- [ ] Database points to local instance with disposable data.

**Staging**
- [ ] `APP_DEBUG=false`, `APP_URL` uses HTTPS.
- [ ] `SESSION_SECURE_COOKIE=true`, cookies scoped to staging domain.
- [ ] CORS restricted to staging origins.
- [ ] Mail delivery verified in staging (no spam).

**Production**
- [ ] `APP_DEBUG=false`, HTTPS enforced at the edge and app.
- [ ] CORS restricts to production origins only.
- [ ] Backups captured, rotation procedures documented and scheduled.

### Pre-deployment Gate
- [ ] `php artisan config:cache` and `php artisan route:cache` succeed with the target environment variables.
- [ ] `php artisan migrate --force` (plus seeders if needed) completes in staging rehearsal.
- [ ] Smoke test: login, create/cancel reservation, verify notification/email.

## Monitoring & Metrics
- `/health` returns Spatie Health JSON results and requires the `X-Secret-Token` header set to `HEALTH_SECRET_TOKEN`.
- `/metrics` exposes Prometheus metrics for whitelisted IPs defined in `PROMETHEUS_ALLOWED_IPS` (default `127.0.0.1`).
- Dashboards should visualise login attempts, throttling events, HTTP traffic, error rates, bookings, cancellations, and occupancy ratio.

### Validation Steps
- **Local:** `php artisan env` to confirm current environment, and `php artisan tinker` → `env('APP_URL')` for spot checks.
- **CI:** Inspect build logs—test jobs must load `.env.testing`; deploy jobs pull environment-scoped secrets.
- **Server:** Run `php artisan config:cache`, then hit `/health` (Spatie Health) to confirm database/cache/mail connectivity.

Following these checklists keeps configuration consistent and secrets protected from development through production.
