# Architecture

This document gives a five-minute tour of how the application is structured so newcomers can picture the moving parts before opening the code.

## 1. Logical view (modules)
- **Auth & Accounts**
  - Laravel Breeze/Fortify controllers, FormRequests, notifications.
  - Guards: `web` for clients and staff, optional API guard for mobile.
  - Responsibilities: registration, login, reset password, enforcing MFA and rate limiting.
- **Rooms & Inventory**
  - Models `Room`, `RoomType`, `RoomStatus`, associated policies.
  - Handles availability, maintenance state, amenities and pricing metadata.
  - Exposes services that answer "which rooms are free between two dates?"
- **Bookings**
  - Orchestrates reservation lifecycle: search, create, update, cancel, check-in/out.
  - Applies business rules (overlap detection, cancellation policy) through domain services and FormRequests.
  - Dispatches events/jobs for emails and in-app notifications.
- **Admin & Back-office**
  - Dashboards for staff and admins (rooms, bookings, users, reports).
  - Authorization enforced by policies and gates (role-based, least privilege).
  - Includes audit trail (Spatie Activitylog) and configuration panels.

## 2. Physical view (current stack, no Docker)
```
Client browser
    │ HTTPS
Apache or Nginx (vhost → public/)
    │ FastCGI
PHP-FPM 8.2 (Laravel 12)
    ├─ MySQL 8 (InnoDB, primary/replica ready)
    ├─ Redis (cache, queues, broadcast) – optional but recommended
    └─ Mailhog / SMTP relay (local dev uses log driver)
```
- Source lives on a single VM (Ubuntu) with PHP-FPM pool; scale horizontally by adding more web nodes behind a load balancer.
- Static assets compiled by Vite are served from `public/`.
- Storage folders mounted with write permissions for `www-data` (symlink `public/storage`).
- SSL termination can live on the reverse proxy (Apache/Nginx) or external ingress.

## 3. Core flows
### 3.1 Availability search
1. Guest enters dates + occupancy.
2. Controller validates via FormRequest and calls `AvailabilityService`.
3. Service queries `room_types` by capacity, joins bookings to exclude overlaps `[check_in, check_out)`.
4. Response includes eligible room types, price breakdown, and available count for UI.

### 3.2 Create Booking
1. Client or staff submits reservation form.
2. FormRequest ensures inputs, then service wraps logic in DB transaction.
3. Service locks a room (pessimistic `SELECT ... FOR UPDATE`), re-checks conflicts, writes booking `pending` or `confirmed`.
4. Event dispatch triggers queue jobs for confirmation email and Reverb broadcast to staff dashboard.

### 3.3 Cancel Booking
1. Cancellation request hits controller guarded by policy (owner or staff).
2. Service calculates penalty (MVP: free before deadline, fee after).
3. Status set to `cancelled`, payments adjusted, notifications sent.

### 3.4 Check-in / Check-out
1. Staff dashboard action (protected route) transitions status.
2. Policy ensures role `employee` or `admin` and booking in correct state.
3. Hooks update room availability, create audit log and optional housekeeping task.

### 3.5 Notifications
1. Domain events (`BookingConfirmed`, `BookingCancelled`) fired from services.
2. Listeners push jobs onto queue (sync in dev) to:
   - Send Mail (log driver locally, SMTP in prod).
   - Broadcast via Laravel Reverb / Pusher to connected dashboards.
   - Record activity log entries.

## 4. Technical decisions
- **MySQL 8 (InnoDB)**  
  Chosen for relational integrity, transactional guarantees, rich date arithmetic, and ease of hosting on shared LAMP stacks. Supports scaling through read replicas and is familiar to most Laravel teams.

- **FormRequests everywhere**  
  Encapsulate validation + authorization per use case, making controllers thin and re-usable (web + API). They also provide centralised sanitisation and provide hooks for custom rules (overlap validation).

- **Policies and gates**  
  Enforce role-based access without leaking logic into controllers/views. Policies cover CRUD on rooms/bookings/users; gates wrap staff-only operations. This keeps the app least-privilege friendly and testable.

- **Spatie Activitylog & Notifications**  
  Provide traceability for admin actions (who cancelled what, when) and consistent notification handling across email and websockets.

- **Redis (optional)**  
  Used when available for faster cache/session/queue handling. When absent, Laravel gracefully falls back to file/array drivers, so local dev remains simple.

## 5. Extension points
- API layer: same services power JSON endpoints for mobile/partner integrations.
- Payment integration: plug provider SDKs into booking pipeline (events already emitted).
- Reporting: data warehouse exports can hook into nightly jobs without touching core services.

Keep this page updated when new modules (billing, housekeeping v2, analytics) are introduced so newcomers always have an accurate mental model.
