# GitHub Project Hygiene

To streamline collaboration on the Riad Projet repo, configure the following items after the initial fork.

## Labels
Create the labels below with consistent colours so automation can filter issues:
- `type:feat` – feature work (suggested colour `#1D76DB`)
- `type:bug` – defects (`#D73A4A`)
- `type:sec` – security fixes (`#B60205`)
- `type:docs` – documentation changes (`#0E8A16`)
- `priority:high` – urgent tasks (`#BFDADC`)
- `good first issue` – beginner-friendly (`#7057FF`)

## Projects
Enable a GitHub Project (Kanban) called **PFE Roadmap** with the columns:
1. Backlog
2. In Progress
3. In Review
4. Done

Automate card movement via workflows when pull requests are opened or merged if possible.

## Milestones
Plan work around the following milestones:
- **MVP** – baseline features needed for the initial demo.
- **Security** – hardening tasks and compliance items.
- **Monitoring** – observability, alerting, analytics.
- **Defense** – deliverables related to your final presentation/defense.

## Automation Tips
- Require issue templates (see `.github/ISSUE_TEMPLATE`) so submissions are structured.
- Use the pull request template to enforce testing notes and changelog awareness.
- Link issues to PRs (`Fixes #123`) so progress is tracked automatically.
- Configure branch protection rules as outlined in [environments.md](environments.md).
