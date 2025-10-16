# Threat Model (OWASP lens)

Scope: current Laravel monolith (web UI + JSON endpoints) hosted on a LAMP stack without Docker. Threats are mapped to OWASP Top 10 and include compensating controls that already exist or are planned.

## 1. Assets
- Guest and staff accounts (authentication secrets, recovery tokens).
- Reservation data (dates, pricing, special requests).
- Payment intents and invoices (even if processed externally).
- Administrative configuration (room availability, pricing rules).
- Logs and audit trails (contain operational evidence).

## 2. Threats and controls
| Category | Example threat | Primary controls | Detection & response |
| --- | --- | --- | --- |
| **Broken Access Control** (A01) | IDOR exposing other guests' bookings; privilege escalation to admin. | Laravel policies/gates on every controller, route middleware per role, FormRequests enforce ownership. Activitylog tracks admin actions. | 403 metrics in observability, weekly audit of role grants, CI run of Laravel policy tests. |
| **Cryptographic Failures** (A02) | Secrets leaked in repo; insecure cookies in prod. | Secrets only in `.env`; CI runs Gitleaks; production config enforces HTTPS, `APP_KEY` rotation process, secure/same-site cookies. | CI secret scans fail PR; runtime alerts when `APP_DEBUG=true` in prod. |
| **Injection** (A03) | SQL injection through booking search filters. | Eloquent/Query Builder use bound parameters; validation sanitises user input; no dynamic SQL. | Error logs, 5xx spikes, WAF rules. |
| **Insecure Design** (A04) | Overbooking via race condition; bypassing cancellation fee. | Booking service uses transactions and locks; business invariants covered by tests; state machine prevents illegal transitions. | Automated feature tests; metrics on double-booking attempts. |
| **Security Misconfiguration** (A05) | Public storage exposing invoices; verbose errors in prod. | Storage symlink controlled; `.env` not committed; deployment checklist sets `APP_DEBUG=false`; CSP, HSTS, X-Frame, X-Content-Type headers configured. | Health checks validate debug OFF; observability alerts on missing headers. |
| **Vulnerable Components** (A06) | Unpatched Composer/NPM packages. | CI runs `composer audit`, `npm audit`, Trivy FS; Dependabot alerts. | CI fails on HIGH/CRITICAL; governance issue opened. |
| **Identification & Auth Failures** (A07) | Brute force on login; session fixation. | Rate limiting (`RATE_LIMIT_AUTH_ATTEMPTS`); Laravel session regeneration; support for 2FA; password length enforcement. | Auth metrics (failed login count) and brute force playbook. |
| **Software Integrity Failures** (A08) | Malicious dependency/injected builds. | Lock files committed; CI verifies signatures when available; SBOM published; releases built from tagged commits. | CI pipeline fails on checksum mismatch; SBOM diff review. |
| **Security Logging & Monitoring Failures** (A09) | Silent account compromise. | Structured JSON logs via stack channel; activity log for bookings; alerts for anomaly spikes (to be wired into Grafana). | Runbook defines response; nightly review of alerts. |
| **Server-Side Request Forgery** (A10) | Abuse of outbound HTTP clients. | Outbound whitelists in config; HTTP client timeouts; no user-controlled URLs in MVP. | Logging of outbound requests; alerts on unexpected domains. |
| **CSRF** (A05 extended) | Forged POST from attacker. | Laravel CSRF middleware; verify tokens on all state-changing routes; `SameSite=lax` cookies. | 419 responses monitored; QA tests. |
| **XSS** (A03 extended) | Stored XSS via booking notes. | Blade auto-escaping; FormRequests strip HTML unless explicitly allowed; CSP blocking inline scripts. | Content Security Policy violation reports (future) and smoke tests. |

## 3. Residual risks
- Social engineering of staff (phishing) can still grant access; mitigate with training and least privilege.
- Denial of Service: current stack can be overwhelmed; rate limiting and WAF rules reduce but cannot eliminate.
- Real-time channel (Reverb/Pusher) depends on API credentials; compromise could leak live updates.
- Manual deployments risk misconfiguration; see deployment checklist to reduce human error.

## 4. Treatment plan
| Risk | Action | Owner | Target date |
| --- | --- | --- | --- |
| DoS on authentication endpoints | Integrate Cloudflare/Fail2ban rules, consider captcha after N failures. | Infra lead | Before public launch |
| Websocket credential leakage | Rotate Reverb keys quarterly; monitor access logs. | Dev lead | Recurring (quarterly) |
| Lack of automated secret rotation | Adopt external secret manager (HashiCorp Vault or AWS Secrets Manager). | Platform team | Phase 2 |
| Missing CSP reporting endpoint | Configure `report-to` header and monitor CSP violations. | Frontend lead | Phase 2 |

## 5. References
- [Security policy](../SECURITY.md)
- [CI security pipeline](ci-security.md)
- [Runbook](runbook.md)
- [Security hardening guide](security-hardening.md)
