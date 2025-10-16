# Changes Log

Lightweight history of major decisions, feature milestones, and security fixes. Update this when shipping notable changes.

| Version / Date | Change | Notes / Impact |
| --- | --- | --- |
| 0.1.0 (2024-06-01) | Initial MVP: room types, bookings, basic admin panel. | Supports manual creation and cancellation, includes seed demo accounts. |
| 0.1.1 (2024-06-05) | Added anti-overlap transaction guard + feature tests. | Prevents double booking under concurrency. |
| 0.2.0 (2024-06-12) | Introduced role-based dashboards (customer, employee, admin). | Policies enforce least privilege; Activitylog enabled. |
| 0.2.1 (2024-06-18) | Security hardening: CSP/HSTS headers, rate limit auth/bookings. | Aligns with SECURITY.md guidelines. |
| 0.3.0 (planned) | Payment provider integration and invoicing. | Requires new tests, updates to booking state machine. |
| 0.3.1 (planned) | Docker deployment pipeline and staging gate. | Depends on CI/CD roadmap in deployment doc. |

## Recording security incidents
- Add entry when CVE applied or incident handled:
  - Date, issue link, CVE ID, remediation summary.

## Decision tracking
- Major architectural decisions (e.g. adopting Redis, introducing queuing system) should get a short paragraph with rationale and alternatives considered.

## How to update
1. Add new row to table with semantic version or date.
2. Link to relevant PR or issue (`[#123](https://github.com/...)`).
3. If deprecating API behaviour, cross-reference `docs/api.md`.

Keeping this log updated accelerates onboarding and audit readiness.
