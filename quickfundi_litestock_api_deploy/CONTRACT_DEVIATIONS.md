# QuickFundi Integration Contract Changes

The earlier provider-oriented API described a schema that is not used by LiteStock's PHP inventory application. The contract has been revised to expose read-only product, stock, and sales data from `inventory_db` instead:

- Provider lookup and provider registration endpoints have been removed.
- Product and sale identifiers and fields now map to `products` and `sales` in the LiteStock schema.
- Inventory is derived from `products.stock_quantity`; no separate inventory table is used.
- Sales responses do not expose internal user or cashier identifiers.

The API implementation now uses these routes and performs read-only queries. Deployment is not complete until the configured Aiven database contains the intended LiteStock tables/data, Render has the correct connection settings, and the updated code has been deployed.
