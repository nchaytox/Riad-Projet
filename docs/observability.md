# Observability

How we keep an eye on the health of the platform, even on a single-node LAMP install.

## 1. Health endpoints
| Endpoint | Purpose | Implementation |
| --- | --- | --- |
| `/livez` | Liveness: process responding. Returns `200 OK` with static JSON. | Simple route guarded by throttle (`return ['status' => 'alive'];`). |
| `/readyz` | Readiness: checks DB and cache connectivity. | Service class pinging database (`DB::connection()->getPdo()`) and `Cache::store()->ping()`. Returns `503` if any dependency fails. |
| `/health` | Composite health (Spatie Health). | Aggregates DB, queue, schedule, storage disk. Returns JSON for dashboards. |

Use Apache/Nginx ACLs or auth to restrict `/health` to internal monitors if necessary.

## 2. Logging
- Default channel: `stack` (daily files) emitting JSON lines for easier ingestion.
- Each log entry includes: timestamp, channel, level, message, context (booking ID, user ID).
- Enable request correlation by adding middleware that generates `X-Request-Id` header and stores in `Log::withContext`.
- Store security sensitive events (login failures, role changes, cancellations) via Spatie Activitylog with minimal PII (no passport numbers, only IDs).

## 3. Metrics
### 3.1 Technical KPIs
- HTTP latency p95 per route (`/reservations`, `/auth/login`).
- Error rates: 4xx and 5xx per minute.
- Queue depth and job processing rate.
- Cache hit ratio (if using Redis).

### 3.2 Business KPIs
- Bookings per day (confirmed vs pending vs cancelled).
- Occupancy percentage (occupied room nights / total available).
- Average lead time between booking and check-in.
- Cancellation reasons distribution (if captured).

### 3.3 Implementation hints
- Use Spatie Health for heartbeat metrics.
- Export Prometheus metrics via `spatie/laravel-prometheus` (already required).
- Instrument critical flows (availability search, booking create) with timers via middleware or service class.

## 4. Dashboards (example using Grafana)
- **Service overview**: request rate, latency, error rate, CPU/memory of PHP-FPM.
- **Booking funnel**: searches vs created bookings vs cancellations.
- **Authentication**: login attempts, failures (highlight spikes).
- **Notifications**: queue throughput, failed jobs, email delivery stats (if available).
- Add alerts:  
  - Error rate > 2% for 5 minutes.  
  - Booking creation failures > 5 in 15 minutes.  
  - Queue length > 50 jobs for 10 minutes.  
  - Occupancy metrics missing (possible ingestion failure).

## 5. Alerting
- Wire Grafana alerts or simple cron scripts sending emails/slack when thresholds exceeded.
- Minimum set:
  - Readiness failing (DB down).
  - Error burst (500 or 429).
  - No bookings recorded in past 24h (business anomaly).

## 6. Local developer tips
- Use Telescope or Laravel Debugbar only in local envs; disable in production.
- Tail logs with `php artisan tail` while testing flows.
- Simulate health check failures by changing DB credentials temporarily (never in prod) to verify alerts trigger.

Keep observability aligned with the runbook so incident responders know where to look first.
