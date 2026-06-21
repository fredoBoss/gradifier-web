const { getPool } = require('./_lib/db');
const { requireAuth } = require('./_lib/auth');

module.exports = async function handler(req, res) {
  const user = requireAuth(req, res);
  if (!user) return;

  const pool = getPool();
  try {
    const [rows] = await pool.execute(
      'SELECT id, name, email, photo FROM user WHERE id = ? LIMIT 1',
      [user.id]
    );
    if (!rows.length) return res.status(404).json({ error: 'User not found' });
    const u = rows[0];
    res.json({
      id: u.id,
      name: u.name || '',
      email: u.email || '',
      photo: u.photo || '/img/fred.png',
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
