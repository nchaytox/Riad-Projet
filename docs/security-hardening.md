# Security Hardening Guide

This guide consolidates the security requirements for Riad Projet, spanning authentication, authorization, validation, infrastructure protections, and operational response.

## 1. Authentication & Account Lifecycle
- **Enrollment & login**
  - Require verified e-mail before granting access.
  - Enforce strong password policy (min length ≥ 12, mix of classes) and hash with **Argon2id**.
  - Apply rate limiting on `login`, `register`, `password.reset`, and `password.email` routes (e.g., `5 requests/min/IP`).
  - Enable 2FA (TOTP or WebAuthn) for employees and admins; optionally allow customers opt-in.
- **Recovery & password changes**
  - Use single-use, expiring reset URLs (default Laravel tokens already expire; configure timeout).
  - Notify users when password or e-mail is changed.
  - Invalidate other sessions after password reset.
- **Sessions**
  - Enforce `Secure` and `SameSite` cookies in staging/production (HTTPS only).
  - Short-lived sessions for back-office roles; limited “remember me” durations for clients.
  - Rotate session IDs on privilege changes and invalidation events.

## 2. Roles, Permissions & Access Control
- Adopt Spatie Permission for RBAC with roles: `client`, `employee`, `admin`.
- Document and maintain an access matrix (CRUD operations per role).
- Implement Laravel Policies per aggregate (`Booking`, `Room`, `RoomType`, `Blackout`, etc.).
- Enforce ownership checks (clients see only their bookings).
- Never trust a `user_id` from input—use authenticated user context and secure route-model binding.
- Protect routes with `auth` middlewares and fine-grained `can:*` checks.

## 3. Validation & Business Integrity
- Use Form Requests for:
  - Date rules (`check_in < check_out`, no past dates).
  - Capacity/guest counts.
  - Monetary amounts and currencies.
  - State transitions (allow only legal status changes).
- Harden mass assignment:
  - Prefer `$guarded = ['*']` with DTO/service-layer mapping, or strictly declared `$fillable`.
- Execute booking and blackout mutations inside transactions that re-check availability; abort with 409 (conflict) on overlaps.
- Blackouts must not invalidate confirmed bookings unless a relocation workflow exists.

## 4. HTTP Defenses (CORS, CSRF, Headers)
- **CSRF:** Laravel protects Blade forms by default; for API/SPAs use Sanctum (cookies + XSRF token) or Bearer tokens with stateless guards.
- **CORS:** Configure per environment—explicit origin whitelist, allowed methods, and headers. No `*` in staging/production.
- **Security headers:** via global middleware, set:
  - `Content-Security-Policy` (default `default-src 'self'` plus required domains).
  - `Strict-Transport-Security` (production).
  - `X-Content-Type-Options: nosniff`.
  - `Referrer-Policy: no-referrer` or `strict-origin-when-cross-origin`.
  - `Permissions-Policy` disabling unused features.
  - `X-Frame-Options: DENY`.

## 5. Logging, Traceability & Privacy
- Write structured (JSON) logs, rotated daily; avoid PII and secrets.
- Include `request_id`, `user_id` (if authenticated), resource, action, outcome, latency.
- Mask sensitive data in logs and responses.
- Use Spatie Activitylog for critical actions (booking create/modify/cancel, check-in/out) with audit trails.
- Show generic error pages in production; full stack traces only in local/testing.
- Map exceptions to accurate HTTP codes: `422` (validation), `403`, `404`, `409`, `429`, `500`.

## 6. Data, Encryption & Secrets
- Maintain unique `APP_KEY` per environment; never commit keys.
- Encrypt data fields only when needed (e.g., payment tokens).
- Ensure database backups are encrypted and tested regularly (restore on staging).
- Store secrets outside git (server `.env`, orchestrator secrets, GitHub environment secrets).
- Run Gitleaks locally and in CI to detect leaked secrets.
- Include `composer audit` in CI and fail on High/Critical advisories.

## 7. Attack Surface Reduction
- Disable or restrict debug tooling (e.g., Telescope behind auth or removed in production).
- Enforce pagination and query limits to prevent enumeration.
- Apply rate-limits on sensitive endpoints (auth flows, booking create/cancel).
- Use ULID/UUID identifiers where exposing sequential IDs is risky.
- Validate uploaded files (type, size), rename them, and store in non-executable buckets/dirs.

## 8. Build & Dependency Security
- Keep Composer/NPM dependencies current; enable Dependabot for Composer and GitHub Actions.
- CI pipeline must run:
  - `composer audit --locked` (fail on High/Critical).
  - Larastan/PHPStan at strict level.
  - Semgrep with Laravel/PHP/OWASP rules (no High findings).
  - SBOM generation (CycloneDX/Syft) and archiving for traceability.

## 9. Security Testing
- **Manual checks**
  - Clients cannot access others’ bookings (403).
  - Concurrent bookings on same room/dates – second attempt receives HTTP 409.
  - POST without CSRF returns 419.
  - Unauthorized cross-origin requests blocked by CORS.
  - Brute-force login triggers HTTP 429 via throttling.
- **Automated coverage**
  - Feature tests assert role-based access, valid transitions, overlap rejections.
  - Larastan at chosen level shows zero errors.
  - Semgrep yields no High alerts.
  - Composer audit reports no High/Critical vulnerabilities.

## 10. Security Observability
- Provide `/health` (Spatie Health) covering DB, cache, queue, mail. Requests must include the `X-Secret-Token` header set to `HEALTH_SECRET_TOKEN`.
- Expose Prometheus metrics at `/metrics`; access is limited to IPs defined in `PROMETHEUS_ALLOWED_IPS`.
- Prometheus counters capture:
  - Login attempts, throttling counts.
  - HTTP request counters and p95 latency per endpoint.
  - Error rates (5xx) and business metrics (bookings, cancellations, occupancy ratio).
- Configure Grafana alerts on:
  - Elevated 5xx rate.
  - Latency p95 breaches.
  - Anomalous login attempts/throttling spikes.

## 11. Security Runbook
Maintain written procedures for:
- **Secret exposure:** rotate credentials, redeploy, invalidate sessions, audit access.
- **Brute-force surge:** tighten rate limits, enable CAPTCHA, block offender IPs, investigate.
- **Critical dependency vuln:** bump package, run regression tests, perform emergency deploy.
- **Data incident:** freeze writes, collect forensic logs, notify stakeholders, implement remediation.

## 12. Definition of Done (Security)
- RBAC and Policies cover all sensitive actions; unauthorized access denied.
- Validation + transactional checks guard bookings and blackouts.
- Security headers, CORS, CSRF aligned with environment.
- Rate limiting active on authentication and booking operations.
- Logs (JSON) and audit trails avoid sensitive data exposure.
- CI passes with tests, Larastan/PHPStan, Semgrep (no High), composer audit (no High/Critical).
- `/health` and `/metrics` endpoints operational with dashboards + alerts.
- Security runbook authored, reviewed, and exercised via tabletop tests.

Keep this document synchronized with implementation changes; update checklists and automation whenever security posture evolves.
