// Run once to bcrypt-hash all plain-text passwords in the DB before deploying.
// Usage: node scripts/hash-passwords.js
// Requires DATABASE_URL in your .env file

require('dotenv').config();
const mysql  = require('mysql2/promise');
const bcrypt = require('bcryptjs');

(async () => {
  const conn = await mysql.createConnection(process.env.DATABASE_URL);
  const [users] = await conn.execute('SELECT id, password FROM user');

  let updated = 0;
  for (const user of users) {
    // Skip if already bcrypt (starts with $2b$ or $2y$)
    if (/^\$2[aby]\$/.test(user.password)) {
      console.log(`User ${user.id}: already hashed, skipping`);
      continue;
    }
    const hash = await bcrypt.hash(user.password, 12);
    await conn.execute('UPDATE user SET password = ? WHERE id = ?', [hash, user.id]);
    console.log(`User ${user.id}: hashed`);
    updated++;
  }

  console.log(`\nDone — ${updated} password(s) updated.`);
  await conn.end();
})();
