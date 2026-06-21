const { getPool } = require('./_lib/db');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).end();

  const { email, newPassword, confirmPassword } = req.body || {};

  if (!email || !newPassword || !confirmPassword)
    return res.status(400).json({ error: 'All fields required' });
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim()))
    return res.status(400).json({ error: 'Invalid email format' });
  if (newPassword !== confirmPassword)
    return res.status(400).json({ error: 'Passwords do not match' });
  if (newPassword.length < 8)
    return res.status(400).json({ error: 'Password must be at least 8 characters' });

  const pool = getPool();
  try {
    const [rows] = await pool.execute(
      'SELECT id FROM user WHERE email = ? LIMIT 1',
      [email.trim()]
    );
    if (!rows.length) return res.status(404).json({ error: 'Email not found' });

    const hash = await bcrypt.hash(newPassword, 12);
    await pool.execute('UPDATE user SET password = ? WHERE email = ?', [hash, email.trim()]);
    res.json({ success: true, message: 'Password updated successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
