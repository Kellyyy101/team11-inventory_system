# QuickFundi Integration Contract Questions

1. Which authentication method should LiteStock and QuickFundi use for the deployed API (for example, a shared API key or bearer token), and how should credentials be issued and rotated?
2. Should Aiven become the live database used by the PHP LiteStock application, or will it hold a replicated copy of `inventory_db`? If it is a copy, what process and freshness guarantee should synchronize product, stock, and sales updates?
3. Does QuickFundi need all sale line records, or should the API provide a reduced or aggregated sales view? The current contract omits cashier and user identifiers.

The current OpenAPI contract specifies product search as a case-insensitive name substring, category as an exact match, an inclusive `from`/`to` sales date range, and a low-stock threshold below 10 units.
