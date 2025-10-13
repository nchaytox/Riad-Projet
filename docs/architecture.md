# Architecture Overview

Riad Projet follows a modular Laravel monolith architecture with clear separation between presentation, domain logic, and infrastructure concerns. The diagram below highlights the primary components.

```mermaid
graph TD
    Browser["SPA / Blade UI"]
    API["HTTP Controllers & API Routes"]
    Services["Application Services<br/>Reservation, Billing, Notifications"]
    Jobs["Queue Workers<br/>Emails, Webhooks, Reports"]
    Events["Domain Events"]
    DB["MySQL<br/>Relational Data"]
    Cache["Redis / Cache Store"]
    Reverb["Laravel Reverb<br/>WebSocket Server"]
    Integrations["External Services<br/>Payments, Email, SMS"]

    Browser -->|Axios / Fetch| API
    API --> Services
    Services --> DB
    Services --> Cache
    Services --> Jobs
    Jobs --> Integrations
    Jobs --> Reverb
    Events --> Jobs
    Reverb --> Browser
```

## Modules
- **Presentation Layer**  
  Blade templates and Vue components served via Vite. Handles authentication, room browsing, reservation creation, and operational dashboards.

- **Reservation Domain**  
  Aggregates booking logic (availability checks, rate calculation, payment capture, cancellation policies). Exposes services consumed by controllers and jobs.

- **Billing & Payments**  
  Manages invoices, payment attempts, refunds, and integration with third-party payment gateways. Responsible for emitting events that trigger notifications.

- **Customer Management**  
  Stores guest profiles, loyalty details, and communication preferences. Links to reservations and invoices for complete history.

- **Inventory & Housekeeping**  
  Tracks room types, amenities, status (available, occupied, maintenance), and scheduling for cleaning tasks.

- **Notifications**  
  Dispatches transactional emails, in-app alerts, and WebSocket events via Laravel Reverb so staff dashboards stay up to date in real time.

## Reservation Flow
```mermaid
sequenceDiagram
    participant Guest
    participant UI as Web UI
    participant API as API / Controller
    participant Service as Reservation Service
    participant DB as Database
    participant Jobs as Queue Jobs
    participant Staff as Staff Dashboard

    Guest->>UI: Select room, dates, guest info
    UI->>API: POST /reservations
    API->>Service: Validate request, check availability
    Service->>DB: Query room inventory, lock slot
    Service->>DB: Create reservation, payment intent
    Service->>Jobs: Dispatch confirmation email job
    Service->>Jobs: Dispatch notification broadcast
    Jobs-->>Staff: Reverb push (new reservation)
    Jobs-->>Guest: Email confirmation with invoice
```

## Data Storage
- **MySQL** stores relational data such as rooms, bookings, invoices, and audit logs.
- **Redis (optional)** is used for caching availability lookups and broadcast queues.
- **Storage** directory holds uploaded assets (receipts, room photos) and generated reports. Credentials and sensitive settings live exclusively in `.env`.

## Operational Concerns
- **Migrations** manage schema versioning. Each feature branch includes its own migration files.
- **Queues** offload heavy work (emails, third-party APIs) to background workers.
- **Observability** uses Laravel logging plus optional integrations like Telescope or Debugbar in non-production environments.
- **Scaling** is achieved by running multiple PHP-FPM workers and Reverb instances behind a load balancer. MySQL replicas handle read-heavy workloads.
