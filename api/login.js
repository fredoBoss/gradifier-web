const { getPool } = require('./_lib/db');
const { signToken } = require('./_lib/auth');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).end();

  const { email, password } = req.body || {};

  if (!email || !password)
    return res.status(400).json({ error: 'Email and password required' });

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim()))
    return res.status(400).json({ error: 'Invalid email format' });

  const pool = getPool();
  try {
    const [rows] = await pool.execute(
      'SELECT id, email, password FROM user WHERE email = ? LIMIT 1',
      [email.trim()]
    );

    const valid = rows.length && await bcrypt.compare(password, rows[0].password);

    await pool.execute(
      'INSERT INTO login_attempts (email, status, timestamp) VALUES (?, ?, NOW())',
      [email.trim(), valid ? 'success' : 'failed']
    ).catch(() => {});

    if (!valid) return res.status(401).json({ error: 'Incorrect credentials' });

    const token = signToken({ id: rows[0].id, email: rows[0].email });
    res.setHeader('Set-Cookie',
      `token=${token}; HttpOnly; Path=/; Max-Age=3600; SameSite=Strict`
    );
    res.json({ success: true });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
