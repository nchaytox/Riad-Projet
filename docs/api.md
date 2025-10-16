# API Reference (draft)

The app exposes a JSON API that mirrors the web flows. This document captures the main routes, payloads, and rules so clients can integrate safely.

## 1. Authentication
- Guard: Laravel Sanctum (token-based personal access tokens).
- Obtain token:
  ```
  POST /api/auth/login
  {
    "email": "guest@example.com",
    "password": "secret"
  }
  ```
  Response:
  ```
  {
    "token": "plain-text-token",
    "user": { ... }
  }
  ```
- Revoke token: `POST /api/auth/logout` with bearer token.
- Rate limit: 5 attempts/minute per IP (`RATE_LIMIT_AUTH_ATTEMPTS`).
- 401 when token missing/invalid; 403 when policy forbids action.

## 2. Rooms endpoints
| Method | Route | Description | Notes |
| --- | --- | --- | --- |
| GET | `/api/room-types` | List room types with capacity and pricing. | Public. Supports query params: `adults`, `children`. |
| GET | `/api/rooms/{room}` | Staff/admin only: detailed room info. | Policy `RoomPolicy@view`. |
| PATCH | `/api/rooms/{room}` | Update status, notes. | Staff (maintenance) or admin. |

Example response:
```json
{
  "id": 12,
  "number": "R12",
  "room_type": {
    "id": 3,
    "name": "Deluxe Suite",
    "capacity_adults": 2,
    "base_price": 18000
  },
  "status": "available",
  "amenities": ["wifi", "balcony"],
  "notes": ""
}
```

## 3. Booking endpoints
| Method | Route | Description | Request schema | Responses |
| --- | --- | --- | --- | --- |
| GET | `/api/bookings` | List current user's bookings (customers) or all (staff/admin). | Query filters: `status`, `from`, `to`. | 200 JSON array. |
| POST | `/api/bookings` | Create booking. | `{ "room_type_id": 3, "check_in": "2024-09-01", "check_out": "2024-09-04", "adults": 2, "children": 0, "notes": "" }` | 201 on success. 409 on overlap. |
| GET | `/api/bookings/{booking}` | Show booking details. | N/A | 200 JSON, includes room + price. |
| PATCH | `/api/bookings/{booking}` | Update dates or guest counts before check-in. | Fields optional. Validate overlap again. | 200 success, 422 validation. |
| POST | `/api/bookings/{booking}/cancel` | Cancel booking. | `{ "reason": "customer_request" }` | 200 with fee info. 409 if past deadline. |
| POST | `/api/bookings/{booking}/check-in` | Staff mark arrival. | Body optional. | 200 success, 403 if not staff. |
| POST | `/api/bookings/{booking}/check-out` | Staff mark departure. | Body optional. | 200 success. |

All booking routes enforce `[check_in, check_out)` interval logic via FormRequest + service layer.

## 4. Errors
- Standard JSON error:
  ```json
  {
    "message": "Validation failed",
    "errors": {
      "check_in": ["must be before check_out"]
    }
  }
  ```
- Error codes:
  - 400 bad request.
  - 401 unauthenticated.
  - 403 forbidden (policy).
  - 404 resource missing.
  - 409 booking overlap conflict.
  - 429 rate limit exceeded.
  - 500 unexpected server error (masked message).

## 5. Rate limits
- Auth endpoints: 5/minute per IP.
- Booking create/cancel: 10/minute per user to prevent spam.
- Admin operations: 30/minute; rely on staff login IPs plus WAF.
- Limits configurable via env (`RATE_LIMIT_BOOKING_ATTEMPTS` etc.).

## 6. Versioning
- Current base path: `/api`.
- When breaking changes occur, introduce `/api/v2` and maintain v1 for a deprecation window.
- Document differences in `docs/changes-log.md`.

## 7. Testing the API
- Postman collection in `docs/api.postman_collection.json` (todo).
- Example curl:
  ```bash
  curl -H "Authorization: Bearer <token>" \
       -H "Accept: application/json" \
       https://riad.example.com/api/bookings
  ```

## 8. Security considerations
- All responses enforce policies; customers never see other bookings.
- Input validated via FormRequests; JSON requests require `Content-Type: application/json`.
- CORS restricted to trusted origins defined in `.env`.
- HTTPS required; tokens leaked over HTTP considered compromised (rotate immediately).
