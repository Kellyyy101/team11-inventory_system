# QuickFundi ↔ LiteStock API

## Overview
This Node.js and Express API exposes LiteStock provider, product/service, sales, and inventory data for consumption by QuickFundi. It also allows QuickFundi to register a provider in LiteStock. Data is stored in MySQL. Included seed records are local sample data, not live QuickFundi data.

## Authentication
**Authentication is not implemented in this lab version.** No token or login is required for local requests. Do not expose this version publicly with real data; authentication is a placeholder for the later security lab.

## Requirements
- Node.js and npm
- MySQL Server (XAMPP MySQL is suitable)

## Run locally from a clean checkout
1. Open a terminal in this project directory.
2. Install packages: `npm install`
3. Start MySQL in XAMPP or your MySQL service. Default config: host `localhost`, user `root`, blank password, port `3306`. If your config differs, set `DB_HOST`, `DB_USER`, `DB_PASSWORD`, and `DB_PORT` environment variables before starting.
4. Run `npm start`. The app creates the `litestock_api` database and tables and seeds sample records only when the providers table is empty.
5. Keep the terminal running. Base URL: `http://localhost:3000`.

PowerShell example for a MySQL password:
```powershell
$env:DB_HOST="localhost"
$env:DB_USER="root"
$env:DB_PASSWORD="YOUR_PASSWORD"
$env:DB_PORT="3306"
npm start
```

## Quickstart: copy and paste
With the server running, try:
```bash
curl http://localhost:3000/api/providers/1
```
Expected response shape if the seeded record remains ID 1:
```json
{ "provider_id": 1, "name": "John Mwangi", "location": "Nairobi", "phone": "0712345678", "description": "Plumbing and general maintenance services", "status": "active" }
```

## Endpoints and examples
Base URL: `http://localhost:3000`. IDs and returned records depend on your local database; examples below reflect the default seed data.

### 1. Get a provider
`GET /api/providers/{provider_id}`
```bash
curl http://localhost:3000/api/providers/1
```
Returns `200` and a provider object containing `provider_id`, `name`, `location`, `phone`, `description`, and `status`.

### 2. Get products/services
`GET /api/products?provider_id={provider_id}`
```bash
curl "http://localhost:3000/api/products?provider_id=1"
```
Example `200` response:
```json
{ "provider_id": 1, "products": [{ "product_id": 1, "name": "Pipe Repair", "description": "Repair of leaking or damaged pipes", "price": 1500 }, { "product_id": 2, "name": "Tap Installation", "description": "Installation of household water taps", "price": 1000 }] }
```

### 3. Get sales
`GET /api/sales?provider_id={provider_id}`
```bash
curl "http://localhost:3000/api/sales?provider_id=1"
```
Example `200` response:
```json
{ "provider_id": 1, "sales": [{ "sale_id": 2, "date": "2026-09-26", "service": "Tap Installation", "quantity": 1, "amount": 1000 }, { "sale_id": 1, "date": "2026-09-25", "service": "Pipe Repair", "quantity": 1, "amount": 1500 }] }
```

### 4. Get inventory
`GET /api/inventory?provider_id={provider_id}`
```bash
curl "http://localhost:3000/api/inventory?provider_id=1"
```
Example `200` response:
```json
{ "provider_id": 1, "inventory": [{ "product_id": 1, "name": "Pipe Repair", "quantity": 25, "unit": "pieces", "status": "in_stock" }, { "product_id": 2, "name": "Tap Installation", "quantity": 10, "unit": "pieces", "status": "in_stock" }] }
```

### 5. Register a provider
`POST /api/providers` with `Content-Type: application/json`.
```bash
curl -X POST http://localhost:3000/api/providers -H "Content-Type: application/json" -d '{"name":"Amina Otieno","location":"Nairobi","phone":"0700000000","description":"Electrical installation and repairs"}'
```
Returns `201` with a `message` and the created `provider` object. The generated ID depends on the database.

## Error reference
Errors are JSON objects with an `error` string. Example: `GET /api/providers/abc` → `400` `{"error":"provider_id must be a positive integer"}`.

| Status | Meaning | Example |
|---|---|---|
| `400` | Invalid ID, missing query parameter, invalid JSON, or invalid POST fields | `GET /api/providers/abc` |
| `404` | No provider matches a valid ID | `GET /api/providers/2147483000` |
| `500` | Unexpected server/database error | Check server logs and database connection |

Not-found response: `GET /api/providers/2147483000` → `404` `{"error":"Provider not found"}`.

## Interactive documentation
With the API running and an internet connection available, open `http://localhost:3000/docs`. It loads Swagger UI from a public CDN and reads the contract from `http://localhost:3000/openapi.yaml`. Alternatively paste `openapi.yaml` into [Swagger Editor](https://editor.swagger.io/). Confirm all five endpoints render.

## Automated tests (Week 7)
With MySQL running, run `npm install` and then `npm test`. Tests use Jest and Supertest against the real Express app and local MySQL database. A seeded provider must exist; start the API once if the database has not yet been seeded.

## Limitations
- Authentication is not implemented.
- Data is local sample data unless connected to a real LiteStock database.
- Partner exchange and blind test are classroom activities; record their actual outcomes in `PARTNER_TEST_RESULTS.md` and `BLIND_TEST_REPORT.md`.
