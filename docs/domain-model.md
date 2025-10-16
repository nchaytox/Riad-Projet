# Domain Model

This note captures the booking domain contract so product, engineering, and QA share the same language.

## 1. Core entities
| Entity | Purpose | Key attributes |
| --- | --- | --- |
| `User` | Any authenticated person (guest, employee, admin). | `name`, `email`, `password`, `role`, `random_key`, `email_verified_at`. |
| `Role` / `Permission` | Authorisation layer (implemented via policies/gates; can be extended with Spatie Permission). | Roles: `customer`, `employee`, `admin` (alias for `super`). |
| `RoomType` | Blueprint for a set of similar rooms. | `name`, `capacity_adults`, optional `capacity_children`, `base_price`, `description`, `amenities`. |
| `Room` | Physical room that can be booked. | `number`, `room_type_id`, `status` (`available`, `maintenance`, `out_of_service`), `floor`, `notes`. |
| `Booking` | Reservation linking a user to a room and time interval. | `code`, `user_id`, `room_id`, `check_in`, `check_out`, `status`, `adults`, `children`, `total_amount`, `currency`, `payment_status`, `cancellation_policy_applied`. |
| `CustomerProfile` *(optional extension)* | Extra guest data if needed (address, preferences). | `user_id`, `phone`, `nationality`, `diet`, etc. |

## 2. Booking state machine
States follow the interval `[check_in, check_out)` (inclusive start, exclusive end). Allowed transitions:
```
draft → pending → confirmed → checked_in → checked_out
             ↘              ↘
              cancelled      no_show
```
- `draft` exists only transiently in forms (not persisted).
- `pending` = reservation captured but payment/validation pending.
- `confirmed` = guaranteed stay.
- `checked_in` and `checked_out` track lifecycle on premises.
- `cancelled` can stem from guest or staff action. No further transitions.
- `no_show` reached automatically if guest never arrives by end of check-in day.
- Guards:
  - Cannot move to `checked_in` unless current date >= `check_in` and status is `confirmed`.
  - Cannot `checked_out` unless currently `checked_in`.
  - Cancellation after check-in becomes early checkout (handled by business rules).

## 3. Anti-overlap rules
Give interval `A = [a1, a2)` for new booking and `B = [b1, b2)` for existing confirmed/pending/checked_in bookings:
- Conflict if `(a1 < b2) AND (b1 < a2)`.
- Blackout periods follow same interval semantics.
- When searching, consider only rooms whose status is `available` for the whole range.
- Insert/update booking inside a database transaction and re-run availability query with `SELECT ... FOR UPDATE` (pessimistic lock) to avoid race conditions.
- If using Laravel validation, add a custom rule that queries bookings/blackouts excluding the current booking (on updates).

## 4. Cancellation policy (MVP vs future)
| Scenario | MVP behaviour | Future extensions |
| --- | --- | --- |
| Cancellation deadline | Free until `N` days before `check_in` (configurable, default 3) | Per-room-type rules, channel specific policies. |
| Late cancellation | Charge first night or fixed fee, mark `cancellation_policy_applied` with rule name | Dynamic fees by rate plan, partial refunds, credit notes. |
| No-show | Treated as cancellation after deadline (fee retained) | Automatic overbooking handling, waiting list activation. |
| Early checkout | Staff marks `checked_out` early; manual adjustment of fees | Automatic proration and housekeeping reschedule. |

Store applied rule name/percentage so finance can reconcile charges.

## 5. Transactional guarantees
1. Wrap create/update/cancel flows in database transactions.
2. Emit domain events only after commit (queue jobs in `afterCommit` listeners).
3. Use optimistic retries when deadlocks occur.
4. Keep monetary operations idempotent (re-running a job must not duplicate charges).

## 6. Authorisation & visibility
- Clients can read and manage only their own bookings.
- Employees may manage any booking but cannot edit platform settings.
- Admins can act on everything, including role changes.
- Enforce via Laravel policies: `BookingPolicy@view`, `@update`, `@cancel`, `@checkIn`, `@checkOut`.

## 7. Derived data & reports
- Occupancy rate: sum of booked nights per day divided by total room nights.
- Cancellation ratio: `cancelled / (confirmed + cancelled)`.
- Average stay length: `(check_out - check_in)` average.
- Keep these as queries or scheduled jobs; avoid denormalising unless performance requires.

Keep this document aligned with migrations and service layer so QA and product specs stay reliable.
