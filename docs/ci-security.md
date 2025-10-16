# CI and Security Pipeline

Goal: make every pull request non-mergable until quality and security signals are green. This page documents what runs, which statuses are required, and how to handle findings.

## 1. What runs on each PR
| Stage | Tooling | Command(s) | Purpose | Required check |
| --- | --- | --- | --- | --- |
| **Tests** | PHPUnit | `php artisan test` | Regression coverage on features and policies. | ✅ `tests-quality` |
| **Static analysis** | Larastan (PHPStan) | `composer analyse` | Type safety and dead-code detection. | ✅ `tests-quality` |
| **Formatting** | Pint, Vite build | `./vendor/bin/pint --test` (or fail on diff) + `npm run build` | Ensures code style and buildable assets. | ✅ `tests-quality` |
| **Composer audit** | Composer built-in | `composer audit --locked` | Detects PHP dependency CVEs. | ✅ `tests-quality` |
| **Semgrep SAST** | Semgrep OSS rulesets | `semgrep ci --config p/owasp-top-ten,p/php` | Finds insecure patterns, deserialisation, injection. | ✅ `static-security` |
| **Gitleaks** | Gitleaks | `gitleaks detect --source . --no-banner` | Blocks committed secrets. | ✅ `static-security` |
| **Trivy FS** | Trivy filesystem scan | `trivy fs --security-checks vuln --severity HIGH,CRITICAL .` | Additional CVE coverage (Composer and npm). | ✅ `sca` |
| **Trivy Config** | Trivy config scan | `trivy config --severity HIGH,CRITICAL .` | Flags insecure IaC (Terraform, YAML). | ✅ `sca` |
| **SBOM** | Anchore/CycloneDX action | `anchore/sbom-action` | Produces software bill of materials artefact. | ⚠️ optional |

All required checks are marked as "Require status checks to pass" on `main` and `develop`. Branch protection also enforces:
- At least one reviewer approval (two preferred for risky changes).
- Branch up to date with base branch.
- Dismiss stale approvals on new commits (recommended).

## 2. After merge workflow
1. `develop` deploys automatically to staging.
2. GitHub Workflow `dast-zap-baseline` runs [OWASP ZAP Baseline](https://www.zaproxy.org/docs/docker/baseline-scan/) against `secrets.STAGING_BASE_URL`.
3. Medium/High findings fail the workflow and open a GitHub issue labelled `security` + `dast`. Production promotion is frozen until closed or accepted with compensating controls.

## 3. Artefacts and visibility
- PHPUnit JUnit, Larastan reports, Composer audit JSON, Semgrep SARIF, Trivy SARIF, SBOM JSON, and ZAP HTML are uploaded as artefacts (14–30 day retention).
- Semgrep and Trivy SARIF feed GitHub *Security → Code scanning alerts* with inline annotations.
- ZAP issues link back to HTML evidence for reproducibility.

## 4. Handling findings and false positives
| Tool | Default action | Suppression rule |
| --- | --- | --- |
| Semgrep | Fix the code. | If truly benign, add inline `// nosemgrep: <rule>` with justification, mention in PR summary, and open follow-up ticket. |
| Gitleaks | Rotate secret immediately, purge history if needed. | Permanent ignores require encrypted baseline file plus security sign-off. |
| Trivy | Upgrade or patch dependency; document CVE ID in PR notes. | Use `.trivyignore` with expiry date and ticket link. |
| Composer audit | Upgrade or replace package. | Only ignore if vendor no longer maintains package and system not exposed; escalate to security. |
| ZAP | Remediate before production. | Temporary waiver via issue describing compensating control, owner, review date. |

No suppression may merge without:
1. Link to tracking issue (security debt).
2. Explicit reviewer acknowledgment.
3. Planned removal date.

## 5. Local pre-flight checklist
Run the same commands locally before pushing to reduce CI churn:
```bash
composer install
npm install
./vendor/bin/pint
composer analyse
php artisan test
composer audit --locked
npm audit --omit=dev
semgrep ci --config "p/owasp-top-ten,p/php"
trivy fs --severity HIGH,CRITICAL .
trivy config --severity HIGH,CRITICAL .
```

## 6. Secrets and environments
- PR workflows do not have access to production/staging secrets.
- Deployment workflows use GitHub Environments with protected secrets: `APP_KEY`, DB credentials, mail, broadcast keys.
- Configure environment protection rules to require approval from release manager before running staging/prod jobs.
- Keep `.env.example` in sync so contributors can reproduce local runs.

## 7. Related references
- [Security policy](../SECURITY.md)
- [Threat model](threat-model.md)
- [Runbook](runbook.md)
- [Deployment guide](deployment.md)
