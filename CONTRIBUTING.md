# Contributing Guide

Thank you for helping us build Riad Projet. The sections below summarise how we collaborate and what we expect from every contribution.

## Workflow Overview
- Fork the repository to your personal GitHub account.
- Keep two long-lived branches:
  - `main` – production, protected and read-only.
  - `develop` – integration branch where pull requests are merged first.
- Create short-lived branches from `develop` with the following prefixes:
  - `feat/*` for new features
  - `fix/*` for bug fixes
  - `chore/*` for maintenance and tooling
  - `docs/*` for documentation-only changes
- Open pull requests back to `develop`. Release candidates are merged from `develop` into `main` once QA is complete.

## Commit Style
We follow [Conventional Commits](https://www.conventionalcommits.org/) to generate clear history and automated changelogs.

Examples:
- `feat(reservations): allow bulk check-in`
- `fix(payments): handle 3DS failure gracefully`
- `chore(repo): project hygiene (docs, templates, ignores, security)`

## Pull Request Checklist
Every pull request must:
- Pass automated tests (`php artisan test`) and include new tests when relevant.
- Have linting satisfied (`./vendor/bin/pint` for PHP; ensure `npm run build` completes without errors for the frontend).
- Update or add documentation when behaviour changes.
- Include a meaningful title and a summary of the changes.
- List manual test steps in the PR description.

## How to Test Locally
```bash
# PHP unit and feature tests
php artisan test

# Static analysis / code style for PHP
./vendor/bin/pint

# Compile frontend assets to ensure no build errors
npm run build
```

For lengthy operations such as `php artisan migrate` or `npm run dev`, consider using [Laravel Sail](https://laravel.com/docs/sail) or Docker to reproduce the production environment locally.

## Code Review Expectations
- Keep pull requests focused—small, scoped changeships are easier to review.
- Draft pull requests are welcome while you gather feedback; mention blockers clearly.
- Respond to review feedback promptly and document any follow-up tasks before merging.

## Reporting Issues
Use the issue templates provided under `.github/ISSUE_TEMPLATE/` to report bugs, request features, or track tasks. Provide logs, reproduction steps, and expected outcomes to help maintainers triage quickly.
