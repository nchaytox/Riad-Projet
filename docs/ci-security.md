# CI & Security Pipeline

Our GitHub Actions pipeline guards quality and security on every branch, pull request, and release. The flow below shows when each workflow runs and which ones block merges.

```
feature push / PR ─┬─▶ Tests & Quality (required)
                   ├─▶ Static Security Checks (required)
                   ├─▶ Dependency & Config Scans (required)
                   └─▶ SBOM Generation (advisory)
                               │
                               ▼
merge to develop  ──────────────┬─▶ Deploy Staging (external)
                                └─▶ ZAP Baseline (blocks prod promotion)
                               ▲
nightly schedule ───────────────┘─▶ Nightly Security Sweep
```

## Workflow Matrix

| Workflow | File | Trigger | Purpose | Blocking? |
| --- | --- | --- | --- | --- |
| **Tests & Quality** | `.github/workflows/tests-quality.yml` | Push to `feat/*`, `fix/*`, `chore/*`, `docs/*`, `refactor/*`; PR → `develop`, `main` | PHPUnit, Larastan, Composer audit, artefacts | ✅ Required check |
| **Static Security Checks** | `.github/workflows/static-security.yml` | PR → `develop`, `main` | Semgrep (SARIF), Gitleaks (diff) | ✅ Required check |
| **Dependency & Config Scans** | `.github/workflows/sca.yml` | PR → `develop`, `main` | Trivy FS & Config (SARIF uploads) | ✅ Required check |
| **SBOM Generation** | `.github/workflows/sbom.yml` | PR → `develop`, `main`; push → `main` | CycloneDX JSON SBOM artefact | ⚠️ Advisory (non-blocking) |
| **ZAP Baseline (Staging)** | `.github/workflows/dast-staging.yml` | `workflow_run` after "Deploy Staging" + manual | Passive DAST against staging, creates issue on Medium/High | Blocks promotion to prod |
| **Nightly Security Sweep** | `.github/workflows/nightly-security.yml` | Daily @ 02:00 UTC + manual | Re-runs audits/SAST/SCA without code changes | Monitoring only |

## Required Checks & Branch Protection

Configure branch protection for `develop` and `main` so the following checks must pass before merging:

- `Tests & Quality / Backend & Frontend Quality`
- `Static Security Checks / Semgrep & Gitleaks`
- `Dependency & Config Scans / Trivy Security Scans`

Require at least one reviewer (two is recommended) and keep "Require branches to be up to date" enabled. ZAP results are reviewed before promoting staging to production but do not block the PR itself.

## What Each Stage Does

### Tests & Quality
- Installs Composer and NPM dependencies with caching.
- Runs `php artisan test --log-junit=…`, `composer analyse -- --error-format=raw`, and `composer audit --locked --format=json`.
- Uploads JUnit, PHPStan, and Composer audit reports as artefacts (retained 14 days).

### Static Security Checks
- Executes Semgrep with the OWASP Top 10 and PHP rulepacks, publishing SARIF to *Security → Code scanning alerts*.
- Runs Gitleaks against the diff; any secret leaks fail the check.

### Dependency & Config Scans
- Trivy filesystem scan for dependencies (`severity=HIGH,CRITICAL`, fails on findings) with SARIF upload.
- Trivy config scan for IaC misconfigurations (fails on HIGH/CRITICAL) with SARIF upload.

### SBOM & Supply Chain
- Generates a CycloneDX JSON SBOM via `anchore/sbom-action`; artefact retained 30 days.

### ZAP Baseline (Staging)
- Runs automatically once the `Deploy Staging` workflow succeeds (or manually via `workflow_dispatch`).
- Scans `secrets.STAGING_BASE_URL`; fails on Medium/High issues via `-w medium` and uploads HTML/JSON reports.
- On failure, opens a GitHub issue with label `security` + `dast` linking to the artefact. Promotion to production is blocked until findings are triaged or justified.

### Nightly Security Sweep
- Re-runs Composer/NPM audits, Semgrep, Gitleaks, and Trivy scans on the default branch to catch newly published CVEs or configuration drift.
- Does **not** upload SARIF (noise control) but will fail the workflow to surface issues in the repository Insights tab.

## Handling Findings & False Positives

| Tool | Preferred action | Suppression guidance |
| --- | --- | --- |
| Semgrep | Fix the issue whenever possible. | Use `// nosemgrep` or `# nosemgrep` with `semgrep-ignore:<rule>` and add justification in the PR description. |
| Gitleaks | Rotate/revoke credentials immediately. | If historical leak must remain, add entry to an encrypted baseline (`.gitleaks.baseline`) and link the remediation ticket. |
| Trivy | Upgrade/patch dependencies or remediate config. | Set `ignore-unfixed` only when a vendor fix is unavailable and open a follow-up issue. Document the CVE and planned remediation date. |
| ZAP | Address Medium/High alerts before production. | Temporary exceptions require an issue describing compensating controls (e.g. WAF rule) and acceptance from the security reviewer. |

All suppressions must include:
1. A link to the tracking ticket.
2. Rationale and compensating controls.
3. A review reminder date.

## Artefacts & Visibility

- **Reports:** test results, PHPStan logs, Composer audit JSON, Trivy SARIF, SBOM, and ZAP reports are uploaded as artefacts (14–30 days retention).
- **Code Scanning:** Semgrep and Trivy SARIF feeds the *Security → Code scanning alerts* dashboard for inline annotations.
- **Issues:** ZAP failures automatically open an issue tagged `security` and `dast`.

## Local Pre-flight Checklist

Before pushing:
```bash
composer install
npm install
./vendor/bin/pint
composer analyse
php artisan test
npm run build
composer audit --locked
npm audit --omit=dev
semgrep ci --config "p/owasp-top-ten,p/php"
trivy fs --severity HIGH,CRITICAL .
```

## Secrets & Environments

- PR workflows run without environment secrets. Deployment workflows (staging/prod) must use GitHub environment secrets for `APP_KEY`, DB credentials, mail, Reverb, etc.
- Configure staging cookies (`SESSION_SECURE_COOKIE=true`) and CORS domains prior to ZAP runs.
- Provide `STAGING_BASE_URL` as a repository secret for the DAST workflow.

## Troubleshooting

- **Failing Semgrep/Gitleaks:** run locally with the same command to reproduce. Commit fixes or document the suppression with justification.
- **Trivy false positives:** confirm the CVE is non-applicable, file a tracking issue, and add an ignore rule with an expiry (documented in the PR).
- **ZAP alerts:** replicate locally using the Docker container (`zaproxy/zap-baseline`) and share evidence in the tracking issue.

## Further Reading

- [Security Hardening Guide](security-hardening.md)
- [Environment & Secrets](environments.md)
- [Runbook](runbook.md)
