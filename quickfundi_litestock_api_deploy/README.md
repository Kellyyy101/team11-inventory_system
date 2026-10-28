# LiteStock API for QuickFundi

## Overview

This Node.js/Express API provides read-only access to LiteStock product, stock, and sales data for QuickFundi. It reads the existing `products` and `sales` tables from LiteStock's `inventory_db` schema. It does not create tables, seed sample records, or write to the database.

The API returns only the product and sales fields QuickFundi needs. It does not expose user accounts, passwords, cashier IDs, or usernames.

## Endpoints

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/products` | List products; optional `search` and `category` filters |
| GET | `/api/products/{product_id}` | Get one product by LiteStock product ID |
| GET | `/api/inventory` | Read current stock; optional `low_stock=true` filter |
| GET | `/api/sales` | Read sales; optional inclusive `from` and `to` dates in `YYYY-MM-DD` format |

The full response schemas and examples are in [`openapi.yaml`](./openapi.yaml). Interactive documentation is served at `/docs`.

## Database configuration

Set the connection settings in the API environment; do not put credentials in source code or commit them:

| Variable | Purpose | Default |
|---|---|---|
| `DB_HOST` | MySQL hostname | `localhost` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_USER` | MySQL account with read access | `root` |
| `DB_PASSWORD` | MySQL password | Empty |
| `DB_NAME` | Database containing LiteStock's `products` and `sales` tables | `inventory_db` |
| `DB_SSL_CA` | PEM certificate authority for TLS connections, such as Aiven | Unset |
| `PORT` | HTTP port supplied by the host | `3000` |

For deployment, configure these variables in the existing hosting service. Use Aiven's hostname, port, database name, user, password, and CA certificate. The account should have only the `SELECT` permissions required for `products` and `sales`. The API does not import, copy, or synchronize the local XAMPP data: the configured database must already contain the LiteStock tables and the current data QuickFundi is meant to see. An import made once will not stay synchronized with a separate local database.

## Run locally

1. Start MySQL and ensure `inventory_db` contains the LiteStock `products` and `sales` tables.
2. Set `DB_HOST`, `DB_PORT`, `DB_USER`, `DB_PASSWORD`, and optionally `DB_NAME` and `DB_SSL_CA`.
3. Run `npm install` and `npm start`.
4. Open `http://localhost:3000/docs`.

## Test

With the configured database available and containing at least one product, run:

```bash
npm test
```

The integration tests issue read-only requests and do not insert, update, or delete database records.

## Deployment notes

Code and configuration changes must be pushed to the branch connected to Render (or deployed using Render's manual deploy) before the hosted service will use them. Updating OpenAPI documentation alone does not change the running API. Keep the Aiven database private; QuickFundi should connect to the API over HTTPS, not directly to MySQL.

Authentication is not currently implemented. Do not expose real business data publicly until an authentication method and access policy have been agreed with QuickFundi and added to the API.
