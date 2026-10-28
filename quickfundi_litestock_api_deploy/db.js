const mysql = require("mysql2/promise");

const dbConfig = {
  host: process.env.DB_HOST || "localhost",
  user: process.env.DB_USER || "root",
  password: process.env.DB_PASSWORD || "",
  port: Number(process.env.DB_PORT || 3306),
  database: process.env.DB_NAME || "inventory_db",
  timezone: "Z",
  ssl: process.env.DB_SSL_CA
    ? { ca: process.env.DB_SSL_CA }
    : undefined,
};

let pool;

async function initializeDatabase() {
  if (pool) {
    await pool.query("SELECT 1");
    return pool;
  }

  pool = mysql.createPool({
    ...dbConfig,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
  });

  try {
    await pool.query(`
      SELECT p.id, p.name, p.category, p.price, p.stock_quantity,
             s.id, s.product_id, s.quantity_sold, s.total_price, s.sale_date
      FROM products p
      LEFT JOIN sales s ON s.product_id = p.id
      LIMIT 0
    `);
    return pool;
  } catch (error) {
    await pool.end();
    pool = undefined;
    throw error;
  }
}

async function closePool() {
  if (pool) {
    await pool.end();
    pool = undefined;
  }
}

function getPool() {
  if (!pool) {
    throw new Error("Database has not been initialized.");
  }
  return pool;
}

module.exports = {
  initializeDatabase,
  getPool,
  closePool,
};
