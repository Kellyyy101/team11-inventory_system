const express = require("express");
const { initializeDatabase, getPool } = require("./db");

const app = express();
const PORT = Number(process.env.PORT || 3000);

app.use(express.json());

function isNonEmptyString(value) {
  return typeof value === "string" && value.trim().length > 0;
}

function isPositiveInteger(value) {
  return Number.isInteger(value) && value > 0;
}

function mapProvider(row) {
  return {
    provider_id: row.provider_id,
    name: row.name,
    location: row.location,
    phone: row.phone,
    description: row.description,
    status: row.status,
  };
}

function mapProduct(row) {
  return {
    product_id: row.product_id,
    name: row.name,
    description: row.description,
    price: Number(row.price),
  };
}

function mapSale(row) {
  return {
    sale_id: row.sale_id,
    date: row.sale_date instanceof Date
      ? row.sale_date.toISOString().slice(0, 10)
      : String(row.sale_date),
    service: row.service,
    quantity: row.quantity,
    amount: Number(row.amount),
  };
}

function mapInventory(row) {
  return {
    product_id: row.product_id,
    name: row.name,
    quantity: row.quantity,
    unit: row.unit,
    status: row.quantity > 0 ? "in_stock" : "out_of_stock",
  };
}

// Week 5 — GET provider details
app.get("/api/providers/:provider_id", async (req, res) => {
  try {
    const providerId = Number(req.params.provider_id);

    if (!isPositiveInteger(providerId)) {
      return res.status(400).json({ error: "provider_id must be a positive integer" });
    }

    const [rows] = await getPool().query(
      `SELECT provider_id, name, location, phone, description, status
       FROM providers
       WHERE provider_id = ?`,
      [providerId]
    );

    if (rows.length === 0) {
      return res.status(404).json({ error: "Provider not found" });
    }

    return res.status(200).json(mapProvider(rows[0]));
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// Week 5 — GET provider products/services
app.get("/api/products", async (req, res) => {
  try {
    const providerId = Number(req.query.provider_id);

    if (!isPositiveInteger(providerId)) {
      return res.status(400).json({
        error: "provider_id query parameter must be a positive integer",
      });
    }

    const [providerRows] = await getPool().query(
      "SELECT provider_id FROM providers WHERE provider_id = ?",
      [providerId]
    );

    if (providerRows.length === 0) {
      return res.status(404).json({ error: "Provider not found" });
    }

    const [rows] = await getPool().query(
      `SELECT product_id, name, description, price
       FROM products
       WHERE provider_id = ?
       ORDER BY product_id`,
      [providerId]
    );

    return res.status(200).json({
      provider_id: providerId,
      products: rows.map(mapProduct),
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// Week 5 — GET provider sales
app.get("/api/sales", async (req, res) => {
  try {
    const providerId = Number(req.query.provider_id);

    if (!isPositiveInteger(providerId)) {
      return res.status(400).json({
        error: "provider_id query parameter must be a positive integer",
      });
    }

    const [providerRows] = await getPool().query(
      "SELECT provider_id FROM providers WHERE provider_id = ?",
      [providerId]
    );

    if (providerRows.length === 0) {
      return res.status(404).json({ error: "Provider not found" });
    }

    const [rows] = await getPool().query(
      `SELECT
         s.sale_id,
         s.sale_date,
         p.name AS service,
         s.quantity,
         s.amount
       FROM sales s
       LEFT JOIN products p ON p.product_id = s.product_id
       WHERE s.provider_id = ?
       ORDER BY s.sale_date DESC, s.sale_id DESC`,
      [providerId]
    );

    return res.status(200).json({
      provider_id: providerId,
      sales: rows.map(mapSale),
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// Week 5 — GET provider inventory
app.get("/api/inventory", async (req, res) => {
  try {
    const providerId = Number(req.query.provider_id);

    if (!isPositiveInteger(providerId)) {
      return res.status(400).json({
        error: "provider_id query parameter must be a positive integer",
      });
    }

    const [providerRows] = await getPool().query(
      "SELECT provider_id FROM providers WHERE provider_id = ?",
      [providerId]
    );

    if (providerRows.length === 0) {
      return res.status(404).json({ error: "Provider not found" });
    }

    const [rows] = await getPool().query(
      `SELECT
         i.product_id,
         p.name,
         i.quantity,
         i.unit
       FROM inventory i
       INNER JOIN products p ON p.product_id = i.product_id
       WHERE i.provider_id = ?
       ORDER BY i.product_id`,
      [providerId]
    );

    return res.status(200).json({
      provider_id: providerId,
      inventory: rows.map(mapInventory),
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// Week 6 — POST/register provider
app.post("/api/providers", async (req, res) => {
  try {
    const { name, location, phone, description } = req.body;

    // Validation happens BEFORE the database write.
    if (
      !isNonEmptyString(name) ||
      !isNonEmptyString(location) ||
      !isNonEmptyString(phone) ||
      !isNonEmptyString(description)
    ) {
      return res.status(400).json({
        error: "name, location, phone and description are required and must be non-empty strings",
      });
    }

    const [result] = await getPool().query(
      `INSERT INTO providers
       (name, location, phone, description)
       VALUES (?, ?, ?, ?)`,
      [
        name.trim(),
        location.trim(),
        phone.trim(),
        description.trim(),
      ]
    );

    const [rows] = await getPool().query(
      `SELECT provider_id, name, location, phone, description, status
       FROM providers
       WHERE provider_id = ?`,
      [result.insertId]
    );

    return res.status(201).json({
      message: "Provider registered successfully",
      provider: mapProvider(rows[0]),
    });
  } catch (error) {
    console.error(error);
    return res.status(500).json({ error: "Internal server error" });
  }
});

// JSON parsing errors
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
      console.log(`QuickFundi/LiteStock API running at http://localhost:${PORT}`);
    });
  } catch (error) {
    console.error("Failed to start server:", error.message);
    process.exit(1);
  }
}

startServer();
