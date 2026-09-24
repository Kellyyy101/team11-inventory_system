const express = require('express');
const cors = require('cors');
const db = require('./db');

const app = express();
app.use(cors());
app.use(express.json());

// Helper: Standard 404 response matching openapi.yaml
const sendNotFound = (res) => {
  return res.status(404).json({
    error: 'USER_NOT_FOUND',
    message: 'No user exists with the specified userId.'
  });
};

// Helper: Maps database row to OpenAPI schema structure
const mapUserSchema = (user) => {
  let mappedRole = 'student';
  if (user.role === 'owner' || user.role === 'admin') {
    mappedRole = 'administrator';
  } else if (user.role === 'attendant') {
    mappedRole = 'security';
  }

  return {
    userId: Number(user.id),
    name: String(user.username),
    email: `${String(user.username).toLowerCase()}@strathmore.edu`,
    role: mappedRole
  };
};

// 1. GET /users?search={query}
app.get('/users', async (req, res) => {
  const { search } = req.query;

  if (search !== undefined) {
    if (typeof search !== 'string' || search.trim().length < 2) {
      return res.status(400).json({
        error: 'INVALID_SEARCH_QUERY',
        message: 'Search query must be at least 2 characters long.'
      });
    }

    try {
      const [rows] = await db.query(
        'SELECT id, username, role FROM users WHERE username LIKE ?',
        [`%${search.trim()}%`]
      );

      const mappedUsers = rows.map(mapUserSchema);

      return res.status(200).json({
        users: mappedUsers,
        count: mappedUsers.length
      });
    } catch (err) {
      return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
    }
  }

  return sendNotFound(res);
});

// 2. GET /users/:userId/verification
app.get('/users/:userId/verification', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);

  try {
    const [rows] = await db.query('SELECT id FROM users WHERE id = ?', [userId]);

    if (rows.length === 0) {
      return sendNotFound(res);
    }

    return res.status(200).json({
      userId: Number(rows[0].id),
      verified: true,
      verificationStatus: 'verified'
    });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

// 3. GET /users/:userId/role
app.get('/users/:userId/role', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);

  try {
    const [rows] = await db.query('SELECT id, username, role FROM users WHERE id = ?', [userId]);

    if (rows.length === 0) {
      return sendNotFound(res);
    }

    const mapped = mapUserSchema(rows[0]);

    return res.status(200).json({
      userId: mapped.userId,
      role: mapped.role
    });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

// 4. GET /users/:userId/contact
app.get('/users/:userId/contact', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);

  try {
    const [rows] = await db.query('SELECT id, username FROM users WHERE id = ?', [userId]);

    if (rows.length === 0) {
      return sendNotFound(res);
    }

    return res.status(200).json({
      userId: Number(rows[0].id),
      email: `${String(rows[0].username).toLowerCase()}@strathmore.edu`,
      phone: null
    });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

// 5. GET /users/:userId
app.get('/users/:userId', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);

  try {
    const [rows] = await db.query('SELECT id, username, role FROM users WHERE id = ?', [userId]);

    if (rows.length === 0) {
      return sendNotFound(res);
    }

    return res.status(200).json(mapUserSchema(rows[0]));
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

// Unmatched route handler
app.use((req, res) => sendNotFound(res));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on http://localhost:${PORT}`);
});