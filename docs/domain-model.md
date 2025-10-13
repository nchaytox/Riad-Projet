# Booking Domain Guide

This document captures the functional scope, data structures, and behavioural rules behind the Riad Projet booking engine. It acts as a contract between product, engineering, and QA teams.

## 1. Scope & Time Units
- **Atomic unit:** the night.
- **Dates, not datetimes:** all availability logic works on dates in the hotel’s local timezone. Persist `DATE` columns in the database using this timezone.
- **Interval semantics:** `[check_in, check_out)` – inclusive on the start date, exclusive on the end date. A guest arriving on 10th and leaving on 12th occupies the room on the 10th and 11th nights, freeing it on the 12th after check-out.
- **Back-to-back bookings:** allowed (e.g. booking A `[10→12)`, booking B `[12→14)`).
- **Timezone:** single application timezone (hotel local time) defined in configuration.

## 2. Conceptual Data Model
### 2.1 Room Types & Rooms
| Table | Key Columns | Notes |
| --- | --- | --- |
| `room_types` | `id`, `name`, `capacity`, `base_price`, `description`, `active`, optional `max_children`, `amenities` | Extendable via pivot tables for amenities. |
| `rooms` | `id`, `number` (unique), `room_type_id` (FK), `status` (`available`, `maintenance`, `out_of_service`), `floor`, `notes` | Index: `(room_type_id, status)`; status drives availability. |
| `blackout_dates` | `id`, `room_id` (nullable for global blackout), `date_start`, `date_end`, `reason` | Use the same `[start, end)` semantics; treat day-long blackouts as `[start, end+1)`. |

### 2.2 Bookings & Payments
| Table | Key Columns | Notes |
| --- | --- | --- |
| `bookings` | `id`, `code`, `user_id`, `room_id`, `check_in`, `check_out`, `adults`, `children`, `status` (`pending`, `confirmed`, `cancelled`, `checked_in`, `checked_out`, `no_show`), `total_amount`, `currency`, `payment_status` (`unpaid`, `partial`, `paid`), `cancellation_policy_applied`, `notes`, timestamps | Constraints: `check_in < check_out`. Index: `(room_id, check_in, check_out, status)`. |
| `payments` *(optional for MVP)* | `id`, `booking_id`, `amount`, `method`, `provider_tx_id`, `status`, timestamps | Add when simulating or integrating payment gateways. |

### 2.3 Roles & Audit
- **Users:** leverage Laravel’s `users` table with Spatie Permission for roles (`client`, `employee`, `admin`).
- **Activity log:** Spatie Activitylog records actions (create/edit/cancel/check-in/out) without PII/secrets.

## 3. Core Business Rules
### 3.1 Availability & Overlap
- Booking interval: `[a1, a2)`, other booking `[b1, b2)`.
- Overlap detection: intervals clash if `a1 < b2` **and** `b1 < a2`.
- For a room to be available:
  - No active bookings (statuses `confirmed`, `checked_in`, optionally `pending`) overlapping the requested interval.
  - No blackout affecting the same period.
  - Room status is `available`.
- Search strategy: filter by `room_types.capacity`, then count rooms without conflicts; assign a specific room at confirmation time.

### 3.2 Booking State Machine
```
pending ──(payment/validation)──► confirmed ──(guest arrival on check-in date)──► checked_in ──(guest departure)──► checked_out
    ╲                                    ╲                                           ╲
     ╲                                     ╲                                          ╲
      ╲                                     ╲──(no arrival on day)──► no_show          ╲
       ╲                                                                           cancelled
        ╲─(policy/guest)──► cancelled
```
- `pending → confirmed`: deposit received or staff validation.
- `confirmed → checked_in`: only on the check-in date (unless manual override).
- `checked_in → checked_out`: on departure date after check-out.
- `confirmed/pending → cancelled`: subject to cancellation policy.
- `confirmed → no_show`: guest did not arrive by end of check-in day.
- Integrity constraints:
  - Cannot `checked_in` on a different date.
  - Cannot `checked_out` unless currently `checked_in`.
  - Post-check-in cancellation becomes an early checkout (track via flag or dedicated status).

### 3.3 Cancellation Policy (MVP)
- Free cancellation until *N* days before `check_in`.
- After the deadline charge a fee (percentage or first night).
- Record applied policy in `cancellation_policy_applied` and update `payment_status` (retain funds, partial refund, credit).

### 3.4 Pricing (MVP)
- Base price: `room_types.base_price × number_of_nights`.
- Extensions for future iterations: seasonal overrides, coupons, non-refundable rates.

## 4. Concurrency & Transactions
### 4.1 Creating a Booking
1. Re-check availability before writing (do not trust previous search).
2. Run within a database transaction.
3. Conflict handling:
   - **Optimistic:** attempt insert; abort if overlap detected via validation query.
   - **Pessimistic:** select candidate room `FOR UPDATE` (or application lock), re-validate, insert, commit.
4. Trigger notifications/emails only after a successful commit.

### 4.2 Modifying a Booking
- Within a transaction, re-check overlap for the updated interval/room (exclude the booking’s current record).
- Reject changes with a clear error if conflict arises; optionally suggest alternatives.

### 4.3 Blackouts
- On create/update, validate overlap against existing bookings.
- Either block conflicting blackouts or flag impacted bookings for relocation according to policy.

## 5. Indexing & Performance
- `bookings(room_id, check_in, check_out)` – accelerate overlap checks.
- `rooms(room_type_id, status)` – filter by type/capacity and status.
- `blackout_dates(room_id, date_start, date_end)` – quick blackout lookups.
- `users(email)` – unique login.
- Move heavy analytics to async jobs/queues.

## 6. Application-Level Validation
- Form Requests enforce:
  - `check_in < check_out`, dates in the future.
  - Capacity constraints (`adults + children ≤ room_type.capacity`).
  - Non-negative counts for guests.
  - Valid state transitions (e.g., no `checked_out` from `pending`).
- Rate limiting (e.g., throttle reservation create/cancel endpoints).
- Policies/permissions ensure clients see only their bookings; staff/admin have full access with activity logging.

## 7. Business Flows
### 7.1 Search Availability
1. Inputs: `date_arrivee`, `date_depart`, `adults`, `children`.
2. Filter `room_types` by capacity.
3. For each type, count rooms with no overlapping bookings/blackouts.
4. Return available types with total price and inventory.

### 7.2 Create Booking
1. User selects room type, system proposes a room.
2. Transaction: re-validates overlap for chosen room.
3. Persist booking (`pending` or `confirmed`), calculate `total_amount`.
4. Send confirmation email and, if applicable, request deposit/payment.

### 7.3 Modify Booking
1. Transaction: re-check overlap for new dates/room (excluding the booking itself).
2. If available, update booking and recompute price.
3. Notify customer; otherwise, return conflict with alternative suggestions.

### 7.4 Cancel Booking
1. Apply cancellation policy (free vs fee).
2. Update `status=cancelled`, adjust `payment_status`, log policy.
3. Notify customer.

### 7.5 Check-in / Check-out
- **Check-in:** on `check_in` date, transition to `checked_in`; capture arrival details.
- **Check-out:** transition to `checked_out`, release room (set status to `available`), optionally generate invoice.

## 8. Edge Cases & Pitfalls
- Single-night stay: `[2025-10-10, 2025-10-11)` equates to one night.
- Prevent `check_in == check_out`.
- DST/clock shifts: irrelevant because only dates are stored.
- No overbooking in MVP (no waitlist).
- `rooms.status="maintenance"` makes the room unavailable regardless of bookings.
- Cleaning buffer: to add a turnaround day, shift check-out boundary to `[check_in, check_out+1)`.

## 9. Test Matrix (Manual/Automated)
- **T1:** Two back-to-back bookings succeed.
- **T2:** Overlapping booking rejected.
- **T3:** Blackout overlapping requested dates blocks reservation.
- **T4:** Concurrent booking attempts on last room – only one succeeds.
- **T5:** Modify booking to free period – success.
- **T6:** Modify booking to occupied period – fail.
- **T7:** Cancellation before deadline – no fee.
- **T8:** Cancellation after deadline – fee applied.
- **T9:** Check-in allowed only on check-in date.
- **T10:** Check-out allowed only after check-in.

## 10. Observability & Metrics
- Counters: `hotel_bookings_total`, `hotel_cancellations_total`.
- Ratios: `hotel_occupancy_ratio` (occupied nights ÷ total room-nights).
- Latency: p95 of availability search and booking endpoints.
- Errors: 4xx/5xx per endpoint to alert on anomalies.

Keep this guide up to date as new pricing models, policies, or operational rules are introduced.
