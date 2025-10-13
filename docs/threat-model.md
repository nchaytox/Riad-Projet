# Threat Model

This document summarises the main security risks for Riad Projet and how we mitigate them. The analysis aligns with the [OWASP Top 10](https://owasp.org/www-project-top-ten/) and focuses on assets that impact guest privacy, payment integrity, and business operations.

## Key Assets
- Guest PII (name, email, phone, ID documents)
- Reservation and billing data
- Payment intents and tokens
- Staff accounts and role assignments
- Operational logs and audit trails

## Threats & Mitigations
| OWASP Risk | Threat Description | Mitigation Controls | Detection & Response |
| --- | --- | --- | --- |
| A01:2021 Broken Access Control | Staff role escalation or cross-tenant data access | Enforce policies via Laravel authorization gates/policies, restrict admin routes, review activity logs with Spatie Activitylog | Alerts on failed authorization, weekly audit of role changes |
| A02:2021 Cryptographic Failures | Leakage of secrets, MITM on non-HTTPS | Serve over HTTPS, store secrets only in `.env`, rotate credentials, use TLS for Reverb/broadcasting | Secret scanning in CI, dependency checks for crypto libraries |
| A03:2021 Injection | SQL or command injection through reservation forms | Use Eloquent/Query Builder bound parameters, validate inputs with Form Requests, disable debug tools in production | Runtime logs, WAF rules for suspicious payloads |
| A04:2021 Insecure Design | Bypassing reservation workflow to overbook rooms | Implement server-side state transitions and invariants, lock inventory transactions, add business rules tests | Monitoring for double bookings, reconciliation reports |
| A07:2021 Identification & Authentication Failures | Credential stuffing against staff logins | Use Laravel Fortify features (throttling, password length), support 2FA, enforce strong passwords, monitor failed logins | Rate limiting metrics, alert on brute force signatures |
| A08:2021 Software & Data Integrity Failures | Compromise via outdated dependencies | Automate `composer audit` / `npm audit`, enable Dependabot, require signatures for production deployments | CI pipeline fails on high severity advisories, monthly review |
| A09:2021 Security Logging & Monitoring Failures | Breach going unnoticed | Centralise logs (Stack driver/ELK), capture reservation state changes, alert on anomalies, retain logs for 180 days | On-call rotation receives alerts, runbook defines incident response |

## Assumptions
- Production runs behind a WAF/Reverse proxy that terminates TLS.
- Database access is restricted to app services via network firewalls.
- Third-party payment provider handles PCI scope; we never store raw card data.

## Residual Risks
- Social engineering against staff cannot be fully mitigated—train staff and enforce least privilege.
- Denial of service against Reverb could degrade real-time UX; fall back to polling when websocket connections fail.
- Misconfigured S3/storage permissions can expose assets; run periodic bucket audits.

## Next Steps
- Integrate automated penetration testing in CI (e.g., OWASP ZAP) before major releases.
- Document data retention periods and anonymisation procedures.
- Perform annual tabletop exercises to rehearse incident response.
