# Operations Runbook

This runbook helps on-call engineers handle incidents, perform routine maintenance, and recover from failures in Riad Projet.

## Incident Severity Levels
- **SEV0 – Outage:** Booking, check-in, or payment flow unavailable for all users.
- **SEV1 – Critical Degradation:** Major features partially unavailable, data corruption risk.
- **SEV2 – Functional Regression:** Non-critical features broken (reports, notifications) with workarounds.
- **SEV3 – Minor Issue:** Cosmetic defects, documentation gaps, isolated errors.

Escalate severity one level higher if guest data or payment integrity is at risk.

## Incident Response Checklist
1. **Acknowledge** alerts within 15 minutes. Assign an incident commander.
2. **Stabilise** the platform—disable problematic integrations, scale up resources, or place the system into maintenance mode: `php artisan down --secret="INCIDENT_TOKEN"`.
3. **Communicate** status in the incident Slack channel and update status page every 30 minutes.
4. **Diagnose** using logs (`storage/logs/laravel.log`), metrics (APM/Dashboard), and recent deployments.
5. **Mitigate** by rolling back, hotfixing, or applying configuration changes.
6. **Verify** recovery with smoke tests (login, new reservation, payment capture).
7. **Document** root cause, remediation, and follow-up tasks within 24 hours.

## Backup & Restore
- **Database Backups:** Automated nightly MySQL dumps to encrypted object storage. Retain last 30 days; test restores weekly.
- **Storage Assets:** Replicate room photos and invoices to versioned S3 buckets. Run checksum validation monthly.
- **Configuration:** Infrastructure-as-code and `.env` templates stored in a secure secrets manager, not git.

**Restore Procedure**
1. Provision a clean MySQL instance.
2. Import the latest dump using `mysql -u root -p < backup.sql`.
3. Rebuild search indexes or caches with `php artisan migrate --force` and `php artisan schedule:run`.
4. Repoint application environment variables to the restored database and trigger a smoke test.

## Deployment Rollback
1. Identify the last known-good release tag (e.g., `release-2024-05-01`).
2. Checkout that tag and deploy through the existing pipeline.
3. Run `php artisan migrate:status`. If newer migrations were applied, execute the corresponding `down()` methods or manual scripts.
4. Clear caches to avoid stale data: `php artisan config:clear`, `php artisan cache:clear`, `php artisan route:clear`.
5. Notify stakeholders once rollback is complete and start a post-incident review.

## Routine Maintenance
- **Weekly:** Apply OS patches, rotate logs, review failed jobs queue.
- **Monthly:** Audit user roles, rotate secrets, run `composer update` and `npm update --latest` in a staging environment.
- **Quarterly:** Conduct disaster recovery drills, review threat model, and validate monitoring coverage.

## Contacts & Escalation
- Primary on-call engineer: refer to the internal roster.
- Security contact: [security@example.com](mailto:security@example.com)
- Hosting provider support: refer to internal credentials vault.

## Security Playbooks
### Secret Exposure
1. Rotate the affected secret (DB, APP_KEY, API token) in the secret store.
2. Redeploy the application with updated credentials.
3. Invalidate active sessions/tokens where applicable.
4. Audit logs to determine scope and document the incident.

### Brute-force Spike
1. Increase rate-limit thresholds (e.g., tighten from 5/min to 3/min) and enable CAPTCHA on login/register.
2. Block offending IPs/subnets via WAF or edge firewall.
3. Monitor authentication metrics until attempts normalise.
4. Communicate mitigation steps in the incident channel and update playbook if improvements are needed.

### Critical Dependency Vulnerability
1. Bump the affected package to a patched release (or apply vendor fix).
2. Run regression tests (`composer qa`, `npm run check`) locally or in CI.
3. Perform an emergency deployment following the hotfix procedure.
4. Record the timeline and notify stakeholders.

### Data Incident
1. Freeze writes (switch to maintenance mode or read-only mode).
2. Capture relevant logs and database snapshots for forensics.
3. Notify leadership and legal/compliance teams as required.
4. Execute remediation plan, restore integrity, and publish a postmortem.
