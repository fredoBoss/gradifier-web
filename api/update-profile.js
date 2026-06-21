const { getPool } = require('./_lib/db');
const { requireAuth } = require('./_lib/auth');
const bcrypt = require('bcryptjs');

module.exports = async function handler(req, res) {
  if (req.method !== 'POST') return res.status(405).end();
  const user = requireAuth(req, res);
  if (!user) return;

  const pool = getPool();
  const { action, name, email, current_password, new_password, confirm_password } = req.body || {};

  try {
    if (action === 'change_password') {
      if (!current_password || !new_password || !confirm_password)
        return res.status(400).json({ error: 'All password fields required' });
      if (new_password !== confirm_password)
        return res.status(400).json({ error: 'Passwords do not match' });
      if (new_password.length < 8)
        return res.status(400).json({ error: 'Password must be at least 8 characters' });

      const [rows] = await pool.execute('SELECT password FROM user WHERE id = ?', [user.id]);
      if (!rows.length) return res.status(404).json({ error: 'User not found' });

      const valid = await bcrypt.compare(current_password, rows[0].password);
      if (!valid) return res.status(401).json({ error: 'Current password is incorrect' });

      const hash = await bcrypt.hash(new_password, 12);
      await pool.execute('UPDATE user SET password = ? WHERE id = ?', [hash, user.id]);
      return res.json({ success: true, message: 'Password updated successfully' });
    }

    if (!name || !email)
      return res.status(400).json({ error: 'Name and email required' });
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim()))
      return res.status(400).json({ error: 'Invalid email format' });

    await pool.execute(
      'UPDATE user SET name = ?, email = ? WHERE id = ?',
      [name.trim(), email.trim(), user.id]
    );
    res.json({ success: true, message: 'Profile updated successfully' });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
