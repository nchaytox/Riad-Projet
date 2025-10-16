# Demo Script

Use this script for live presentations (≈10 minutes). Adapt timings as needed.

## 1. Setup
- Pre-load database with seeders (`php artisan migrate:fresh --seed`).
- Start services: `php artisan serve`, `npm run dev`, optional `php artisan reverb:start`.
- Open three browser tabs: customer portal, staff dashboard, Grafana (or log tail).

## 2. Narrative flow
1. **Intro (1 min)**  
   - Present Riad Projet: manage rooms, bookings, staff workflows.
   - Highlight roles: customer, employee, admin.

2. **Create account & login (1 min)**  
   - Register or login with demo customer (`guest.demo@example.com` / `password`).
   - Show MFA/reset options if enabled.

3. **Search availability (1 min)**  
   - Pick dates, show availability results and pricing.
   - Mention `[check_in, check_out)` logic.

4. **Create booking (2 min)**  
   - Complete form, confirm success message.
   - Point to realtime notification for staff (Reverb toast).
   - Show email log (Mail log channel).

5. **Concurrency test (1 min)**  
   - Attempt overlapping booking for same room/type; show validation error (409/422).
   - Emphasise anti-overlap transaction.

6. **Cancellation policy (1 min)**  
   - Cancel booking, highlight fee calculation (if inside penalty window).
   - Show activity log entry for audit.

7. **Staff dashboard (2 min)**  
   - Login as staff (`staff.demo@example.com` / `password`).
   - Demonstrate check-in then check-out flow.
   - Show occupancy metrics updating (Grafana panel or artisan command output).

8. **Admin panel (1 min)**  
   - Login as admin (`admin.demo@example.com` / `password`).
   - Review bookings list, user management, configuration.
   - Mention policies enforce least privilege.

## 3. Monitoring shout-outs
- Tail logs: `php artisan tail` to show structured JSON entries.
- Hit `/readyz` endpoint to prove health checks working.
- Show CI pipeline summary or latest Semgrep/Gitleaks report in GitHub.

## 4. Plan B (offline demo)
- Prepare screenshots or recorded GIFs for each step:
  - Availability search results.
  - Booking confirmation screen.
  - Overlap error message.
  - Staff check-in screen.
  - Grafana dashboard snippet.
  - CI checks panel with green Semgrep/Gitleaks results.

## 5. Q&A prompts
- How do you prevent double booking? → mention locking strategy.
- How is security handled? → mention policies, rate limits, CI pipeline (Semgrep, Gitleaks, Trivy, ZAP).
- What is next? → Docker roadmap, improved analytics.

Keep the session interactive by asking the jury which scenario (guest vs staff) to follow first.
