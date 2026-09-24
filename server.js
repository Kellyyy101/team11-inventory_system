const express = require('express');
const cors = require('cors');
const db = require('./db');

const app = express();
app.use(cors());
app.use(express.json());

// Helper: Standard 404 response
const sendNotFound = (res) => {
  return res.status(404).json({
    error: 'USER_NOT_FOUND',
    message: 'No user exists with the specified userId.'
  });
};

// Helper: Response schema mapping
const mapUserSchema = (user) => {
  let mappedRole = 'student';
  if (user.role === 'owner' || user.role === 'admin' || user.role === 'administrator') {
    mappedRole = 'administrator';
  } else if (user.role === 'attendant' || user.role === 'security') {
    mappedRole = 'security';
  }

  return {
    userId: Number(user.id),
    name: String(user.username),
    email: user.email ? String(user.email) : `${String(user.username).toLowerCase()}@strathmore.edu`,
    role: mappedRole
  };
};

// ==========================================
// WEEK 6: POST /users (Part B & Part C)
// ==========================================
app.post('/users', async (req, res) => {
  const { name, email, role, phone } = req.body;

  // --- PART B: VALIDATE FIRST, WRITE SECOND ---
  
  // Check 1: Are all required fields present?
  if (!name || !email || !role) {
    return res.status(400).json({
      error: 'INVALID_INPUT',
      message: 'Missing required fields: name, email, and role are required.'
    });
  }

  // Check 2: Are types and values usable?
  if (typeof name !== 'string' || name.trim().length === 0) {
    return res.status(400).json({
      error: 'INVALID_INPUT',
      message: 'Field "name" must be a non-empty string.'
    });
  }

  // Email format check
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (typeof email !== 'string' || !emailRegex.test(email)) {
    return res.status(400).json({
      error: 'INVALID_INPUT',
      message: 'Field "email" must be a valid email address.'
    });
  }

  // Allowed enums for creation according to contract
  const allowedRoles = ['student', 'security'];
  if (!allowedRoles.includes(role)) {
    return res.status(400).json({
      error: 'INVALID_INPUT',
      message: `Field "role" must be one of: ${allowedRoles.join(', ')}.`
    });
  }

  // --- PART C: IMPLEMENT STATE CHANGE ---
  try {
    // Check if user/email already exists (avoiding duplicate writes / 409 Conflict)
    const [existing] = await db.query(
      'SELECT id FROM users WHERE username = ? OR email = ?',
      [name.trim(), email.trim()]
    );

    if (existing.length > 0) {
      return res.status(409).json({
        error: 'USER_ALREADY_EXISTS',
        message: 'A user with the supplied username or email already exists.'
      });
    }

    // Map contract role to DB role enum ('student'/'security' -> 'attendant' or store as is)
    const dbRole = role === 'security' ? 'attendant' : 'owner'; // adjust mapping as required by your DB

    const [result] = await db.query(
      'INSERT INTO users (username, password, role) VALUES (?, ?, ?)',
      [name.trim(), '$2y$10$defaultHashForNewUsers', dbRole]
    );

    const newUserId = result.insertId;

    // Fetch newly created record and return 201 Created with schema mapping
    const [newUsers] = await db.query('SELECT id, username, role FROM users WHERE id = ?', [newUserId]);
    
    const responsePayload = {
      ...mapUserSchema(newUsers[0]),
      email: email.trim() // Reflect supplied email
    };

    return res.status(201).json(responsePayload);

  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

// ==========================================
// WEEK 5: GET ENDPOINTS
// ==========================================

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
      return res.status(200).json({ users: mappedUsers, count: mappedUsers.length });
    } catch (err) {
      return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
    }
  }

  return sendNotFound(res);
});

app.get('/users/:userId/verification', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);
  try {
    const [rows] = await db.query('SELECT id FROM users WHERE id = ?', [userId]);
    if (rows.length === 0) return sendNotFound(res);
    return res.status(200).json({ userId: Number(rows[0].id), verified: true, verificationStatus: 'verified' });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

app.get('/users/:userId/role', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);
  try {
    const [rows] = await db.query('SELECT id, username, role FROM users WHERE id = ?', [userId]);
    if (rows.length === 0) return sendNotFound(res);
    const mapped = mapUserSchema(rows[0]);
    return res.status(200).json({ userId: mapped.userId, role: mapped.role });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

app.get('/users/:userId/contact', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);
  try {
    const [rows] = await db.query('SELECT id, username FROM users WHERE id = ?', [userId]);
    if (rows.length === 0) return sendNotFound(res);
    return res.status(200).json({
      userId: Number(rows[0].id),
      email: `${String(rows[0].username).toLowerCase()}@strathmore.edu`,
      phone: null
    });
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

app.get('/users/:userId', async (req, res) => {
  const userId = parseInt(req.params.userId, 10);
  try {
    const [rows] = await db.query('SELECT id, username, role FROM users WHERE id = ?', [userId]);
    if (rows.length === 0) return sendNotFound(res);
    return res.status(200).json(mapUserSchema(rows[0]));
  } catch (err) {
    return res.status(500).json({ error: 'DATABASE_ERROR', message: err.message });
  }
});

app.use((req, res) => sendNotFound(res));

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => console.log(`Server running on http://localhost:${PORT}`));