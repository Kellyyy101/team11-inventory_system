const request = require("supertest");
const { app } = require("../server");
const { initializeDatabase, getPool, closePool } = require("../db");

let existingProduct;

beforeAll(async () => {
  await initializeDatabase();
  const [rows] = await getPool().query(
    "SELECT id, name, category, price, stock_quantity FROM products ORDER BY id LIMIT 1"
  );
  if (!rows.length) {
    throw new Error("The configured inventory database has no products to test.");
  }
  existingProduct = rows[0];
});

afterAll(async () => {
  await closePool();
});

describe("GET /api/products", () => {
  test("returns products using the LiteStock schema", async () => {
    const response = await request(app).get("/api/products");

    expect(response.status).toBe(200);
    expect(response.body.count).toBeGreaterThan(0);
    const firstProduct = response.body.products[0];
    expect(firstProduct).toEqual(expect.objectContaining({
      id: expect.any(Number),
      name: expect.any(String),
      price: expect.any(Number),
      stock_quantity: expect.any(Number),
    }));
    expect(firstProduct.category === null || typeof firstProduct.category === "string").toBe(true);
    expect(response.body.products[0]).not.toHaveProperty("provider_id");
  });

  test("supports name and category filters", async () => {
    const nameResponse = await request(app)
      .get("/api/products")
      .query({ search: existingProduct.name });
    expect(nameResponse.status).toBe(200);
    expect(nameResponse.body.products.some((product) => product.id === existingProduct.id)).toBe(true);

    if (existingProduct.category) {
      const categoryResponse = await request(app)
        .get("/api/products")
        .query({ category: existingProduct.category });
      expect(categoryResponse.status).toBe(200);
      expect(categoryResponse.body.products.every(
        (product) => product.category === existingProduct.category
      )).toBe(true);
    }
  });

  test("rejects empty filters", async () => {
    const response = await request(app).get("/api/products?search=%20");
    expect(response.status).toBe(400);
    expect(response.body).toHaveProperty("error");
  });
});

describe("GET /api/products/:product_id", () => {
  test("returns a product by LiteStock product ID", async () => {
    const response = await request(app).get(`/api/products/${existingProduct.id}`);

    expect(response.status).toBe(200);
    expect(response.body).toEqual(expect.objectContaining({
      id: existingProduct.id,
      name: existingProduct.name,
      price: Number(existingProduct.price),
      stock_quantity: existingProduct.stock_quantity,
    }));
  });

  test("rejects invalid IDs and returns 404 for missing products", async () => {
    const invalidResponse = await request(app).get("/api/products/abc");
    expect(invalidResponse.status).toBe(400);

    const missingResponse = await request(app).get("/api/products/2147483000");
    expect(missingResponse.status).toBe(404);
    expect(missingResponse.body.error).toBe("Product not found");
  });
});

describe("GET /api/inventory", () => {
  test("returns stock quantities and statuses", async () => {
    const response = await request(app).get("/api/inventory");

    expect(response.status).toBe(200);
    expect(response.body.inventory).toEqual(expect.arrayContaining([
      expect.objectContaining({
        product_id: existingProduct.id,
        stock_quantity: expect.any(Number),
        status: expect.stringMatching(/^(out_of_stock|low_stock|in_stock)$/),
      }),
    ]));
  });

  test("filters low-stock products and rejects invalid filter values", async () => {
    const response = await request(app).get("/api/inventory?low_stock=true");
    expect(response.status).toBe(200);
    expect(response.body.inventory.every((item) => item.stock_quantity < 10)).toBe(true);

    const invalidResponse = await request(app).get("/api/inventory?low_stock=yes");
    expect(invalidResponse.status).toBe(400);
  });
});

describe("GET /api/sales", () => {
  test("returns sales without internal user fields", async () => {
    const response = await request(app).get("/api/sales");

    expect(response.status).toBe(200);
    expect(response.body).toHaveProperty("count", response.body.sales.length);
    if (response.body.sales.length) {
      expect(response.body.sales[0]).toEqual(expect.objectContaining({
        id: expect.any(Number),
        product_id: expect.any(Number),
        product_name: expect.any(String),
        sale_date: expect.stringMatching(/^\d{4}-\d{2}-\d{2}T/),
        quantity_sold: expect.any(Number),
        total_price: expect.any(Number),
      }));
      expect(response.body.sales[0]).not.toHaveProperty("user_id");
      expect(response.body.sales[0]).not.toHaveProperty("username");
    }
  });

  test("validates date filters and applies an inclusive date range", async () => {
    const filteredResponse = await request(app)
      .get("/api/sales")
      .query({ from: "2026-01-01", to: "2026-12-31" });
    expect(filteredResponse.status).toBe(200);
    expect(filteredResponse.body.sales.every(
      (sale) => sale.sale_date >= "2026-01-01" && sale.sale_date < "2027-01-01"
    )).toBe(true);

    const invalidDateResponse = await request(app).get("/api/sales?from=2026-02-30");
    expect(invalidDateResponse.status).toBe(400);

    const reversedRangeResponse = await request(app)
      .get("/api/sales")
      .query({ from: "2026-12-31", to: "2026-01-01" });
    expect(reversedRangeResponse.status).toBe(400);
  });
});
