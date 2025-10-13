# Security Policy

## Supported Versions
Security updates are guaranteed for:
- `main` – latest production release
- `develop` – upcoming release currently under active development

Older branches are considered end-of-life and will not receive security fixes.

## Reporting a Vulnerability
If you discover a vulnerability, please contact the maintainers privately at [security@example.com](mailto:security@example.com) with:
- A description of the issue and potential impact
- Steps to reproduce (proof of concept) or a failing test if available
- Any logs, stack traces, or configuration details that help us reproduce the issue

We aim to acknowledge reports within **48 hours** and provide an initial assessment within **5 business days**. Coordinate disclosure timelines with us; we prefer to release fixes before details are published.

## Handling Secrets
- Never commit `.env` files or other secret material to source control.
- Rotate credentials immediately if you suspect a secret has leaked.
- Use `.env.example` for non-sensitive defaults and clearly document required variables.
- When sharing debugging details, redact tokens, passwords, and customer data.

## Secure Development Checklist
- Keep dependencies updated (`composer update` and `npm update`) and review changelogs for security patches.
- Run automated security tooling (Dependabot, Composer audit, NPM audit) and address high severity findings promptly.
- Validate and sanitise all user-provided input and never trust client-side enforcement alone.
- Enforce HTTPS in production and configure session/cookie security flags.
- Log security events (failed logins, suspicious reservations) and review them regularly.

Thank you for helping us keep Riad Projet safe for our users.
