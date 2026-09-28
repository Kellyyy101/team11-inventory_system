Week 5–6 Verification Evidence
------------------------------
Run the server with:

```bash
npm install
npm start
```

Base URL:

```text
http://localhost:3000
```

Week 5 GET tests
------------------------------
 1. Provider details

```http
GET /api/providers/1
```

Expected: `200 OK` with `provider_id`, `name`, `location`, `phone`, `description`, and `status`.

Nonexistent provider:

```http
GET /api/providers/99999
```

Expected: `404 Not Found`.

2. Products/services

```http
GET /api/products?provider_id=1
```

Expected: `200 OK`.

Nonexistent provider:

```http
GET /api/products?provider_id=99999
```

Expected: `404 Not Found`.

3. Sales

```http
GET /api/sales?provider_id=1
```

Expected: `200 OK`.

Nonexistent provider:

```http
GET /api/sales?provider_id=99999
```

Expected: `404 Not Found`.

4. Inventory

```http
GET /api/inventory?provider_id=1
```

Expected: `200 OK`.

Nonexistent provider:

```http
GET /api/inventory?provider_id=99999
```

Expected: `404 Not Found`.

## Week 6 POST tests

### Valid request

```http
POST /api/providers
Content-Type: application/json
```

```json
{
  "name": "Mary Wanjiku",
  "location": "Nairobi",
  "phone": "0798765432",
  "description": "Electrical installation and repair services"
}
```

Expected: `201 Created`.

Then verify that the new provider exists using the returned `provider_id`:

```http
GET /api/providers/{new_provider_id}
```

Expected: `200 OK`.

 Missing required field

```json
{
  "name": "Mary Wanjiku",
  "location": "Nairobi",
  "phone": "0798765432"
}
```

Expected: `400 Bad Request`.

No database row should be created.

Wrong field type

```json
{
  "name": 12345,
  "location": "Nairobi",
  "phone": "0798765432",
  "description": "Electrical installation and repair services"
}
```

Expected: `400 Bad Request`.

No database row should be created.

Empty string

```json
{
  "name": "",
  "location": "Nairobi",
  "phone": "0798765432",
  "description": "Electrical installation and repair services"
}
```

Expected: `400 Bad Request`.

No database row should be created.

Week 6 verification checklist
------------------------------------------------
- [x] GET endpoints return real database data.
- [x] GET responses are mapped to the OpenAPI response shape.
- [x] Missing providers return 404.
- [x] POST validates required fields before writing.
- [x] POST rejects wrong types.
- [x] POST rejects empty strings.
- [x] Successful POST returns 201 Created.
- [x] Successful POST returns the newly created provider.
