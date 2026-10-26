# Contract Questions

These are the three contract questions/ambiguities to confirm with the upstream partner before integration:

1. For `GET /api/providers/{provider_id}`, should an inactive provider still be returned, or should the API return `404 Not Found` for inactive providers?

2. For `GET /api/products?provider_id={provider_id}`, does "product" represent a physical inventory item, a service offered by an artisan, or both? The consumer currently treats the resource as a provider's products/services.

3. For `GET /api/sales?provider_id={provider_id}`, should QuickFundi receive complete sales records, or should the producer expose only aggregated activity information because individual sales may contain business-sensitive information?
