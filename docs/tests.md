# Testing Strategy

Target: give contributors a clear idea of expected coverage and how to test critical flows.

## 1. Coverage goals
- **Feature tests:** cover primary user journeys (auth, booking create/cancel, staff actions). Aim for >70% of controllers.
- **Unit tests:** pure domain logic (date rules, pricing calculators, policies). Aim for >80% branch coverage on services/utilities.
- **Browser tests (optional):** use Laravel Dusk only for smoke flows if time allows.

## 2. Test pyramid
| Layer | Tools | Focus |
| --- | --- | --- |
| Unit | PHPUnit | Date helpers, overlap detection, cancellation fee calculator. |
| Feature | PHPUnit HTTP tests | Auth roles, reservations (create/update/cancel), check-in/out transitions, notifications fired. |
| Integration | HTTP tests with database + queue | Realistic booking scenarios using seeders and queue fakes. |
| E2E (optional) | Dusk/Postman | End-to-end booking journey if manual QA needed. |

## 3. Key feature tests
- `test_customer_can_create_booking_when_room_available`.
- `test_booking_request_is_rejected_when_interval_overlaps`.
- `test_employee_can_check_in_confirmed_booking`.
- `test_admin_can_cancel_and_fee_is_recorded`.
- `test_client_cannot_access_other_customer_booking` (policy enforcement).
- `test_notifications_dispatched_on_booking_confirmed`.
- `test_cancellation_deadline_respected`.

## 4. Unit tests
- Availability validator: `[start, end)` interval comparisons.
- Cancellation policy calculator: different deadlines and fees.
- Price builder: base price * nights, optional discounts.
- Policy methods: `BookingPolicy::update`, `BookingPolicy::cancel`.
- Helpers in `app/Helpers/Helper.php`.

## 5. Data fixtures
- Use database seeders for realistic rooms/room types.
- Dedicated factories: `UserFactory` (roles), `RoomFactory`, `BookingFactory`.
- `.env.testing` uses SQLite in-memory for fast runs; keep migrations compatible.
- For feature tests requiring multiple states, leverage `DatabaseTransactions` trait.

## 6. Running test suites
```bash
php artisan test             # full suite (default)
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```
- Fast QA: `composer qa` (lint + analyse + tests).
- Parallel testing optional: `php artisan test --parallel`.

## 7. CI expectations
- Every PR must pass `php artisan test`.
- Add new tests whenever behaviour changes.
- Regression bug fixes include failing test first.

## 8. Future improvements
- Add Dusk smoke tests for booking funnel.
- Integrate mutation testing (Infection) on critical services.
- Add contract tests for API responses (OpenAPI schema validation).
