# Incident Runbook

Reference playbook for on-call responders. Keep this nearby when the pager rings.

## 1. Quick triage
1. Acknowledge alert (within 15 min) and assign an incident commander.
2. Identify impact: bookings blocked? admin only? data at risk?
3. Update status channel every 30 min until resolved.

## 2. Toolbelt
- Logs: `storage/logs/laravel.log`, server syslog, queue failure logs.
- Health endpoints: `/livez`, `/readyz`, `/health`.
- Metrics dashboard: Grafana board "Riad Overview".
- DB access: MySQL client with read replica/backup credentials.
- Queue commands: `php artisan queue:failed`, `php artisan queue:retry`.

## 3. Playbooks
### 3.1 Database outage
- **Symptoms:** `/readyz` returns 503, bookings fail with SQL errors.
- **Diagnose:** check MySQL service status, review recent migrations, inspect disk space.
- **Actions:**
  1. Enter maintenance mode if writes are failing: `php artisan down --secret="incident"`.
  2. Restart database service or failover to replica.
  3. Validate connectivity (`php artisan tinker` -> `DB::select('select 1')`).
  4. Run smoke tests (login, booking create).
  5. Exit maintenance mode: `php artisan up`.
- **Postmortem:** capture root cause, restore point, follow-up tasks.

### 3.2 Mass 500 errors
- **Symptoms:** Error rate > 5%, logs filled with stack traces.
- **Diagnose:** identify recent deploy or config change; inspect exception messages; check queue workers.
- **Actions:**
  1. Roll back to last stable release (see deployment guide).
  2. Clear caches only if needed (`php artisan config:clear` etc.).
  3. Re-run failing tests locally to reproduce.
  4. If caused by bad data, craft hotfix migration or manual SQL patch.
- **Communication:** inform stakeholders on status channel, provide ETA.

### 3.3 Brute-force or credential stuffing
- **Symptoms:** login failures spike, rate limit logs show floods, alerts from auth dashboard.
- **Diagnose:** review `laravel.log` for IP ranges, check WAF/firewall analytics.
- **Actions:**
  1. Tighten rate limits temporarily (`RATE_LIMIT_AUTH_ATTEMPTS=3`), deploy config cache.
  2. Block offending IPs at edge (fail2ban, Cloudflare, security group).
  3. Force password reset if compromise suspected.
  4. Monitor metrics until normal.
- **Follow-up:** review security policy, add captcha or MFA requirement.

### 3.4 Secret leak exposure
- **Symptoms:** Gitleaks or ZAP alert, or manual report.
- **Actions:**
  1. Identify affected secret (APP_KEY, DB password, Reverb key).
  2. Rotate secret in vault/ENV immediately.
  3. Redeploy application with new secret.
  4. Invalidate sessions/tokens where applicable.
  5. Document incident, notify security contact (`security@example.com`).

### 3.5 Booking overlap bug
- **Symptoms:** duplicated room allocation, guests double-booked.
- **Actions:**
  1. Pause new bookings (maintenance mode or feature flag).
  2. Investigate offending bookings (`bookings` table) and determine overlap.
  3. Fix data manually (reassign room) and recalc occupancy.
  4. Add regression test covering scenario; ensure transaction/locking logic intact.

## 4. Diagnostics checklist
- `php artisan env` (ensure not accidentally in debug).
- `php artisan schedule:work --once` (verify scheduler).
- `php artisan queue:work --once` (test queue).
- `php artisan horizon:status` (if Horizon enabled).
- `mysqlshow` / `SHOW PROCESSLIST` for long-running queries.

## 5. Rollback procedure (code)
1. Identify last known good tag or commit.
2. Redeploy (sync files) without running new migrations.
3. Run `php artisan migrate:status` to ensure DB matches expected version.
4. Clear caches: `php artisan config:cache`, `php artisan route:cache`.
5. Verify via smoke tests.

## 6. DB restore drill
1. Retrieve latest encrypted dump from backup storage.
2. Restore into staging DB (`mysql -u <user> -p <db> < backup.sql`).
3. Point staging `.env` to restored DB.
4. Run `php artisan migrate --force` to apply pending migrations.
5. Execute regression smoke tests.
6. Document duration; target < 30 minutes.

## 7. Communication templates
- **Initial alert:** "Investigating SEV1 booking outage. Impact: all bookings failing. Next update in 30 min."
- **Stabilised:** "Bookings restored. Monitoring for 15 min before closing incident."
- **Postmortem link:** share within 24h including cause, fix, prevention.

## 8. Contact list
- Incident commander rotation: see internal roster (Google Sheet).
- Security contact: `security@example.com`.
- Hosting provider: see secrets manager vault entry `infra/hosting`.

Keep this runbook updated after every incident; add new playbooks as we learn.
