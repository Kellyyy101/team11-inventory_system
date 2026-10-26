const request = require('supertest');
const { app } = require('../server');
const { initializeDatabase, getPool, closePool } = require('../db');

let providerId;
let createdProviderId;

beforeAll(async () => {
  await initializeDatabase();
  const [rows] = await getPool().query('SELECT provider_id FROM providers ORDER BY provider_id LIMIT 1');
  if (!rows.length) throw new Error('Test database has no provider seed data. Start the API once to seed it.');
  providerId = rows[0].provider_id;
});

afterAll(async () => {
  if (createdProviderId) await getPool().query('DELETE FROM providers WHERE provider_id = ?', [createdProviderId]);
  await closePool();
});

describe('GET /api/providers/:provider_id', () => {
  test('returns a provider matching the documented shape', async () => {
    const res = await request(app).get(`/api/providers/${providerId}`);
    expect(res.status).toBe(200);
    expect(res.body).toEqual(expect.objectContaining({ provider_id: providerId, name: expect.any(String), location: expect.any(String), phone: expect.any(String), description: expect.any(String), status: expect.stringMatching(/^(active|inactive)$/) }));
  });
  test('rejects a non-numeric ID', async () => {
    const res = await request(app).get('/api/providers/abc');
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
  test('returns 404 for a provider that does not exist', async () => {
    const res = await request(app).get('/api/providers/2147483000');
    expect(res.status).toBe(404); expect(res.body.error).toBe('Provider not found');
  });
});

describe('GET /api/products', () => {
  test('returns products as an array with contract fields', async () => {
    const res = await request(app).get(`/api/products?provider_id=${providerId}`);
    expect(res.status).toBe(200); expect(res.body.provider_id).toBe(providerId); expect(Array.isArray(res.body.products)).toBe(true);
    if (res.body.products.length) expect(res.body.products[0]).toEqual(expect.objectContaining({ product_id: expect.any(Number), name: expect.any(String), description: expect.any(String), price: expect.any(Number) }));
  });
  test('rejects missing provider_id', async () => {
    const res = await request(app).get('/api/products');
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
  test('returns 404 for unknown provider', async () => {
    const res = await request(app).get('/api/products?provider_id=2147483000');
    expect(res.status).toBe(404); expect(res.body.error).toBe('Provider not found');
  });
});

describe('GET /api/sales', () => {
  test('returns sales as an array with contract fields', async () => {
    const res = await request(app).get(`/api/sales?provider_id=${providerId}`);
    expect(res.status).toBe(200); expect(res.body.provider_id).toBe(providerId); expect(Array.isArray(res.body.sales)).toBe(true);
    if (res.body.sales.length) {
      expect(res.body.sales[0]).toEqual(expect.objectContaining({ sale_id: expect.any(Number), date: expect.any(String), service: expect.any(String), quantity: expect.any(Number), amount: expect.any(Number) }));
      expect(res.body.sales[0].date).toMatch(/^\d{4}-\d{2}-\d{2}/);
    }
  });
  test('rejects a non-positive provider_id', async () => {
    const res = await request(app).get('/api/sales?provider_id=0');
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
  test('returns 404 for unknown provider', async () => {
    const res = await request(app).get('/api/sales?provider_id=2147483000');
    expect(res.status).toBe(404); expect(res.body.error).toBe('Provider not found');
  });
});

describe('GET /api/inventory', () => {
  test('returns inventory as an array with contract fields', async () => {
    const res = await request(app).get(`/api/inventory?provider_id=${providerId}`);
    expect(res.status).toBe(200); expect(res.body.provider_id).toBe(providerId); expect(Array.isArray(res.body.inventory)).toBe(true);
    if (res.body.inventory.length) expect(res.body.inventory[0]).toEqual(expect.objectContaining({ product_id: expect.any(Number), name: expect.any(String), quantity: expect.any(Number), unit: expect.any(String), status: expect.stringMatching(/^(in_stock|out_of_stock)$/) }));
  });
  test('rejects invalid provider_id', async () => {
    const res = await request(app).get('/api/inventory?provider_id=-1');
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
  test('returns 404 for unknown provider', async () => {
    const res = await request(app).get('/api/inventory?provider_id=2147483000');
    expect(res.status).toBe(404); expect(res.body.error).toBe('Provider not found');
  });
});

describe('POST /api/providers', () => {
  test('creates a provider and returns the created record that can be fetched again', async () => {
    const res = await request(app).post('/api/providers').send({ name: 'Jest Test Provider', location: 'Nairobi', phone: '0700000011', description: 'Automated test provider' });
    expect(res.status).toBe(201); expect(res.body.message).toBe('Provider registered successfully');
    expect(res.body.provider).toEqual(expect.objectContaining({ provider_id: expect.any(Number), name: 'Jest Test Provider', location: 'Nairobi', phone: '0700000011', description: 'Automated test provider', status: 'active' }));
    createdProviderId = res.body.provider.provider_id;
    const verify = await request(app).get(`/api/providers/${createdProviderId}`);
    expect(verify.status).toBe(200); expect(verify.body.name).toBe('Jest Test Provider');
  });
  test('rejects missing required fields and does not create a row', async () => {
    const [before] = await getPool().query("SELECT COUNT(*) AS total FROM providers WHERE name = 'Invalid Jest Provider'");
    const res = await request(app).post('/api/providers').send({ name: 'Invalid Jest Provider' });
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
    const [after] = await getPool().query("SELECT COUNT(*) AS total FROM providers WHERE name = 'Invalid Jest Provider'");
    expect(Number(after[0].total)).toBe(Number(before[0].total));
  });
  test('rejects wrong field types', async () => {
    const res = await request(app).post('/api/providers').send({ name: 123, location: 'Nairobi', phone: '0712345678', description: 'Test' });
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
  test('rejects an empty string', async () => {
    const res = await request(app).post('/api/providers').send({ name: '   ', location: 'Nairobi', phone: '0712345678', description: 'Test' });
    expect(res.status).toBe(400); expect(res.body).toHaveProperty('error');
  });
});
