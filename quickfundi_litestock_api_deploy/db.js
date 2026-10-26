const mysql = require("mysql2/promise");


const DB_NAME = process.env.DB_NAME || "litestock_api";

const dbConfig = {
  host: process.env.DB_HOST || "localhost",
  user: process.env.DB_USER || "root",
  password: process.env.DB_PASSWORD || "",
  port: Number(process.env.DB_PORT || 3306),
  database: DB_NAME,
  ssl: process.env.DB_SSL_CA
    ? { ca: process.env.DB_SSL_CA }
    : undefined,
};

let pool;

async function initializeDatabase() {
  // Connect without selecting a database so the database can be created automatically.
  const connection = await mysql.createConnection(dbConfig);

  await connection.query(
    `CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
     CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci`
  );

  await connection.end();

  pool = mysql.createPool({
    ...dbConfig,
    database: DB_NAME,
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0,
  });

  await pool.query(`
    CREATE TABLE IF NOT EXISTS providers (
      provider_id INT AUTO_INCREMENT PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      location VARCHAR(100) NOT NULL,
      phone VARCHAR(20) NOT NULL,
      description TEXT NOT NULL,
      status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
  `);

  await pool.query(`
    CREATE TABLE IF NOT EXISTS products (
      product_id INT AUTO_INCREMENT PRIMARY KEY,
      provider_id INT NOT NULL,
      name VARCHAR(100) NOT NULL,
      description TEXT NOT NULL,
      price DECIMAL(10,2) NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT fk_products_provider
        FOREIGN KEY (provider_id) REFERENCES providers(provider_id)
        ON DELETE CASCADE
    )
  `);

  await pool.query(`
    CREATE TABLE IF NOT EXISTS sales (
      sale_id INT AUTO_INCREMENT PRIMARY KEY,
      provider_id INT NOT NULL,
      product_id INT NULL,
      sale_date DATE NOT NULL,
      quantity INT NOT NULL,
      amount DECIMAL(10,2) NOT NULL,
      CONSTRAINT fk_sales_provider
        FOREIGN KEY (provider_id) REFERENCES providers(provider_id)
        ON DELETE CASCADE,
      CONSTRAINT fk_sales_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE SET NULL
    )
  `);

  await pool.query(`
    CREATE TABLE IF NOT EXISTS inventory (
      inventory_id INT AUTO_INCREMENT PRIMARY KEY,
      provider_id INT NOT NULL,
      product_id INT NOT NULL,
      quantity INT NOT NULL DEFAULT 0,
      unit VARCHAR(30) NOT NULL,
      CONSTRAINT fk_inventory_provider
        FOREIGN KEY (provider_id) REFERENCES providers(provider_id)
        ON DELETE CASCADE,
      CONSTRAINT fk_inventory_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON DELETE CASCADE
    )
  `);

  // Seed data only when the database is empty.
  const [providerRows] = await pool.query(
    "SELECT COUNT(*) AS count FROM providers"
  );

  if (providerRows[0].count === 0) {
    const [providerResult] = await pool.query(
      `INSERT INTO providers
       (name, location, phone, description)
       VALUES (?, ?, ?, ?)`,
      [
        "John Mwangi",
        "Nairobi",
        "0712345678",
        "Plumbing and general maintenance services",
      ]
    );

    const providerId = providerResult.insertId;

    const [productResult] = await pool.query(
      `INSERT INTO products
       (provider_id, name, description, price)
       VALUES
       (?, ?, ?, ?),
       (?, ?, ?, ?)`,
      [
        providerId,
        "Pipe Repair",
        "Repair of leaking or damaged pipes",
        1500,
        providerId,
        "Tap Installation",
        "Installation of household water taps",
        1000,
      ]
    );

    const pipeRepairId = productResult.insertId;
    const tapInstallationId = pipeRepairId + 1;

    await pool.query(
      `INSERT INTO inventory
       (provider_id, product_id, quantity, unit)
       VALUES
       (?, ?, ?, ?),
       (?, ?, ?, ?)`,
      [
        providerId,
        pipeRepairId,
        25,
        "pieces",
        providerId,
        tapInstallationId,
        10,
        "pieces",
      ]
    );

    await pool.query(
      `INSERT INTO sales
       (provider_id, product_id, sale_date, quantity, amount)
       VALUES
       (?, ?, ?, ?, ?),
       (?, ?, ?, ?, ?)`,
      [
        providerId,
        pipeRepairId,
        "2026-09-25",
        1,
        1500,
        providerId,
        tapInstallationId,
        "2026-09-26",
        1,
        1000,
      ]
    );
  }

  return pool;
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
