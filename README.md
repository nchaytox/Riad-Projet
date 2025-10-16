# Riad Projet

Riad Projet is a booking and property management platform built with Laravel. It handles reservations, room inventory, payments, customer profiles, and real-time staff notifications.

## Tech Stack
- Laravel 12 (PHP 8.2+)
- MySQL 8 (compatible with MariaDB)
- Vite, Vue, Tailwind CSS for the SPA experience
- Laravel Reverb for real-time notifications

## Quick Start
1. **Clone & bootstrap**
   ```bash
   git clone https://github.com/nchaytox/Riad-Projet.git
   cd Riad-Projet
   cp .env.example .env
   ```
2. **Configure `.env`**
   - Set `APP_URL`, `FRONTEND_URL`
   - Configure `DB_*` values for your MySQL instance
   - Provide mail and broadcast credentials if you want notifications
3. **Install dependencies**
   ```bash
   composer install
   npm install
   ```
4. **Generate app key and storage links**
   ```bash
   php artisan key:generate
   php artisan storage:link
   ```
5. **Migrate & seed**
   ```bash
   php artisan migrate --seed
   ```
6. **Run the stack**
   ```bash
   # Terminal 1
   php artisan serve

   # Terminal 2 (real-time updates)
   php artisan reverb:start

   # Terminal 3 (frontend assets)
   npm run dev
   ```

## Demo Credentials
Use the seeded super-admin account to explore the dashboard:
- Email: `admin@example.com`
- Password: `password`

You can update or create more demo accounts with `php artisan tinker` or by editing the seeders.

## Application URLs
- Web app: `http://localhost:8000`
- API base: `http://localhost:8000/api`
- Reverb socket: `ws://127.0.0.1:6001`

## Repository Structure
```
├── app/                 # Domain logic, models, jobs, listeners
├── bootstrap/           # Framework bootstrap files
├── config/              # Application configuration
├── database/            # Factories, migrations, seeders
├── public/              # Front controller and built assets
├── resources/           # Blade views, Vue components, assets
├── routes/              # HTTP, API, and broadcasting routes
├── tests/               # PHPUnit + Pest tests
├── docs/                # Architecture, threat model, runbooks
└── .github/             # Issue / PR templates & workflows
```

## Documentation
- [Architecture Overview](docs/architecture.md)
- [Threat Model](docs/threat-model.md)
- [Runbook](docs/runbook.md)
- [CI Security Checks](docs/ci-security.md)
- [Booking Domain Guide](docs/domain-model.md)
- [Security Hardening](docs/security-hardening.md)
- [Environment & Secrets](docs/environments.md)
- [Deployment Topology](docs/deployment-topology.md)
- [Project Management Guide](docs/project-management.md)

## Security CI
- Workflows enforce automated testing, SAST, dependency scanning, SBOM generation, and nightly sweeps. See [CI & Security Pipeline](docs/ci-security.md) for full details.
- Required checks before merge: Tests & Quality, Static Security Checks, and Dependency & Config Scans. Configure branch protection on `develop` and `main` to require these statuses and at least one review.
- After deployment to staging, the ZAP Baseline workflow scans `${{ secrets.STAGING_BASE_URL }}`; promotion to production is blocked until any Medium/High alerts are triaged.
- Provide the following repository secrets: `STAGING_BASE_URL` (ZAP), `HEALTH_SECRET_TOKEN`, and environment-specific secrets for staging/production deployments (DB, APP_KEY, mail, Reverb).

## Environment Layout
| Environment | Purpose | Configuration Source |
| --- | --- | --- |
| Local development | Feature work & manual QA | `.env` derived from `.env.example` |
| Automated tests | Deterministic CI and local test runs | `.env.testing` (SQLite, log mailer) |
| Staging | Production rehearsal | Secrets scoped to the **staging** GitHub environment / server |
| Production | Customer traffic | Secrets scoped to the **production** GitHub environment / server |

## Branching & Releases
- `main` – production ready, protected, no direct pushes
- `develop` – integration branch where feature PRs land first
- Short-lived branches use the prefixes `feat/*`, `fix/*`, `chore/*`, or `docs/*`

Create branches from `develop`, open pull requests toward `develop`, and promote to `main` via a release PR once QA is complete.

## Support & Policies
- Contributions: see [CONTRIBUTING.md](CONTRIBUTING.md)
- Code of Conduct: see [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- Security reporting process: see [SECURITY.md](SECURITY.md)
- License: [MIT](LICENSE)
- Repository operations: see [docs/environments.md](docs/environments.md) and [docs/project-management.md](docs/project-management.md)

## Quality Gates
- Run `composer lint`, `composer analyse`, and `composer test` before opening a PR; `composer qa` chains them together.
- Frontend pipelines use `npm run lint` (ESLint + Vue rules) and `npm run check` (lint + production build).
- Execute `php artisan migrate:fresh --seed` on a clean database to confirm migrations and seeders work end-to-end (CI loads `.env.testing`).
- Keep dependencies updated via `composer update` and `npm install` in feature branches; review changelogs for breaking changes.
- Track CI status on pull requests—branch protection requires green builds.

## Data Hygiene
- Logs rotate daily through the `daily` channel; never log unmasked PII or secrets.
- Secrets stay outside git—configure them via GitHub Actions secrets and `.env` files based on `.env.example`.
- `storage/.gitignore` prevents uploads and caches from being committed; leave it in place.
- Do not commit SQL dumps or log archives. Use encrypted CI artefacts or dedicated storage for sharing datasets.

## Definition of Ready
- Branch created from an up-to-date `develop` and rebased if conflicts arise.
- Scope documented: linked issue with acceptance criteria, labels, milestone, and project column updated.
- Local QA scripts (`composer qa`, `npm run check`, `php artisan migrate:fresh --seed`) succeed.
- Tests, seeds, and documentation updated to cover the change.
- Required secrets and environment variables available in the target environment.
