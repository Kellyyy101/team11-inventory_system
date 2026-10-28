# Testing Guide

## Database-backed API tests

Configure the API environment to point to a MySQL database containing LiteStock's `products` and `sales` tables, then run:

```bash
npm test
```

Jest and Supertest verify product listing and filtering, single-product lookup, inventory status and low-stock filtering, sales responses and date filtering, and invalid-input handling. Tests use only `SELECT` queries and do not modify database records. At least one product must exist in the configured database.

## Manual checks

With the API running, use the actual product IDs returned by `GET /api/products`:

- `GET /api/products`
- `GET /api/products?search=flour`
- `GET /api/products?category=Grains`
- `GET /api/products/{product_id}`
- `GET /api/inventory`
- `GET /api/inventory?low_stock=true`
- `GET /api/sales`
- `GET /api/sales?from=2026-10-01&to=2026-10-28`

Invalid product IDs, filters, and date ranges should return `400`; a valid but unknown product ID should return `404`.

## Contract checks

Open `/docs` while the service is running and confirm the four read-only endpoints are shown. Check that the documented server URL and response fields match the deployed service and that sales responses do not include cashier or user identifiers.
