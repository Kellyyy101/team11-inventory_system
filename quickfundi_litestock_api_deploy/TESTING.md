# Testing Guide

## Manual endpoint checks
With MySQL running, start the API using `npm start`, then try:
- `GET http://localhost:3000/api/providers/1`
- `GET http://localhost:3000/api/products?provider_id=1`
- `GET http://localhost:3000/api/sales?provider_id=1`
- `GET http://localhost:3000/api/inventory?provider_id=1`
- `GET http://localhost:3000/api/providers/abc` (expect 400)
- `GET http://localhost:3000/api/providers/2147483000` (expect 404)

Use Postman or curl for `POST /api/providers`; the body must contain non-empty strings `name`, `location`, `phone`, and `description`. Successful creation returns 201; invalid fields return 400. Re-fetch the returned ID to verify persistence.

## Automated tests
Run `npm test` with MySQL running. Jest and Supertest test all five API endpoints, response fields/types, validation failures, not-found responses, and successful POST persistence.

## Partner tests
Exchange `openapi.yaml` (not your test file) with your ring partner and execute their tests against the running API. Record actual findings in `PARTNER_TEST_RESULTS.md`; do not invent partner results.
