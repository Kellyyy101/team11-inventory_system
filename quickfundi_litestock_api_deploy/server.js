const express = require("express");
const path = require("path");
const { initializeDatabase, getPool } = require("./db");

const app = express();
const PORT = Number(process.env.PORT || 3000);
const LOW_STOCK_THRESHOLD = 10;

app.use(express.json());

app.get("/docs", (req, res) => res.sendFile(path.join(__dirname, "docs.html")));
app.get("/openapi.yaml", (req, res) => res.type("text/yaml").sendFile(path.join(__dirname, "openapi.yaml")));

function isPositiveInteger(value) {
  return /^[1-9]\d*$/.test(value) && Number.isSafeInteger(Number(value));
}

function getOptionalQueryString(value) {
  if (value === undefined) {
    return { value: undefined };
  }
  if (typeof value !== "string" || value.trim() === "") {
    return { error: "Query filters must be non-empty strings." };
  }
  return { value: value.trim() };
}

function isValidDate(value) {
  if (typeof value !== "string" || !/^\d{4}-\d{2}-\d{2}$/.test(value)) {
    return false;
  }
  const parsed = new Date(`${value}T00:00:00.000Z`);
  return Number.isFinite(parsed.getTime()) && parsed.toISOString().slice(0, 10) === value;
}

function mapProduct(row) {
  return {
    id: row.id,
    name: row.name,
    category: row.category,
    price: Number(row.price),
    stock_quantity: row.stock_quantity,
  };
}

function mapInventoryItem(row) {
  let status = "in_stock";
  if (row.stock_quantity === 0) {
    status = "out_of_stock";
  } else if (row.stock_quantity < LOW_STOCK_THRESHOLD) {
    status = "low_stock";
  }

  return {
    product_id: row.id,
    name: row.name,
    category: row.category,
    stock_quantity: row.stock_quantity,
    status,
  };
}

function mapSale(row) {
  const saleDate = row.sale_date instanceof Date
    ? row.sale_date.toISOString()
    : `${String(row.sale_date).replace(" ", "T")}Z`;

  return {
    id: row.id,
    product_id: row.product_id,
    product_name: row.product_name,
    sale_date: saleDate,
    quantity_sold: row.quantity_sold,
    total_price: Number(row.total_price),
  };
}

app.get("/api/products", async (req, res) => {
  const search = getOptionalQueryString(req.query.search);
  const category = getOptionalQueryString(req.query.category);

  if (search.error || category.error) {
    return res.status(400).json({ error: search.error || category.error });
  }

  try {
    const conditions = [];
    const parameters = [];

    if (search.value !== undefined) {
      conditions.push("LOWER(name) LIKE LOWER(?)");
      parameters.push(`%${search.value}%`);
    }
    if (category.value !== undefined) {
      conditions.push("category = ?");
      parameters.push(category.value);
    }

    const where = conditions.length ? `WHERE ${conditions.join(" AND ")}` : "";
    const [rows] = await getPool().query(
      `SELECT id, name, category, price, stock_quantity
       FROM products
       ${where}
       ORDER BY name, id`,
      parameters
    );
    const products = rows.map(mapProduct);

    return res.status(200).json({ count: products.length, products });
  } catch (error) {
    console.error("GET /api/products failed:", error.message);
    return res.status(500).json({ error: "Internal server error" });
  }
});

app.get("/api/products/:product_id", async (req, res) => {
  const { product_id: productId } = req.params;
  if (!isPositiveInteger(productId)) {
    return res.status(400).json({ error: "product_id must be a positive integer" });
  }

  try {
    const [rows] = await getPool().query(
      `SELECT id, name, category, price, stock_quantity
       FROM products
       WHERE id = ?`,
      [Number(productId)]
    );
    if (rows.length === 0) {
      return res.status(404).json({ error: "Product not found" });
    }

    return res.status(200).json(mapProduct(rows[0]));
  } catch (error) {
    console.error("GET /api/products/:product_id failed:", error.message);
    return res.status(500).json({ error: "Internal server error" });
  }
});

app.get("/api/inventory", async (req, res) => {
  const { low_stock: lowStock } = req.query;
  if (lowStock !== undefined && lowStock !== "true" && lowStock !== "false") {
    return res.status(400).json({ error: "low_stock must be true or false" });
  }

  try {
    const where = lowStock === "true"
      ? `WHERE stock_quantity < ${LOW_STOCK_THRESHOLD}`
      : "";
    const [rows] = await getPool().query(
      `SELECT id, name, category, stock_quantity
       FROM products
       ${where}
       ORDER BY name, id`
    );
    const inventory = rows.map(mapInventoryItem);

    return res.status(200).json({ count: inventory.length, inventory });
  } catch (error) {
    console.error("GET /api/inventory failed:", error.message);
    return res.status(500).json({ error: "Internal server error" });
  }
});

app.get("/api/sales", async (req, res) => {
  const from = req.query.from;
  const to = req.query.to;
  if (
    (from !== undefined && !isValidDate(from)) ||
    (to !== undefined && !isValidDate(to))
  ) {
    return res.status(400).json({ error: "from and to must be valid YYYY-MM-DD dates" });
  }
  if (from && to && from > to) {
    return res.status(400).json({ error: "from must be on or before to" });
  }

  try {
    const conditions = [];
    const parameters = [];

    if (from) {
      conditions.push("s.sale_date >= ?");
      parameters.push(`${from} 00:00:00`);
    }
    if (to) {
      conditions.push("s.sale_date < DATE_ADD(?, INTERVAL 1 DAY)");
      parameters.push(`${to} 00:00:00`);
    }

    const where = conditions.length ? `WHERE ${conditions.join(" AND ")}` : "";
    const [rows] = await getPool().query(
      `SELECT s.id, s.product_id, p.name AS product_name, s.sale_date,
              s.quantity_sold, s.total_price
       FROM sales s
       INNER JOIN products p ON p.id = s.product_id
       ${where}
       ORDER BY s.sale_date DESC, s.id DESC`,
      parameters
    );
    const sales = rows.map(mapSale);

    return res.status(200).json({ count: sales.length, sales });
  } catch (error) {
    console.error("GET /api/sales failed:", error.message);
    return res.status(500).json({ error: "Internal server error" });
  }
});

app.use((error, req, res, next) => {
  if (error instanceof SyntaxError && error.status === 400 && "body" in error) {
    return res.status(400).json({ error: "Invalid JSON request body" });
  }
  return next(error);
});

async function startServer() {
  try {
    await initializeDatabase();
    app.listen(PORT, () => {
      console.log(`LiteStock API listening on port ${PORT}`);
    });
  } catch (error) {
    console.error("Failed to start LiteStock API:", error.message);
    process.exit(1);
  }
}

if (require.main === module) {
  startServer();
}

module.exports = { app, startServer };
