const mysql = require('mysql2/promise');
const fs    = require('fs');
const path  = require('path');

const DB_URL = process.env.DB_URL;
if (!DB_URL) { console.error('❌ DB_URL env var is not set'); process.exit(1); }

async function run() {
  const conn = await mysql.createConnection({
    uri: DB_URL,
    ssl: { rejectUnauthorized: false },
    multipleStatements: false,
  });
  console.log('✅ Connected\n');

  // Drop and recreate Finger_classes with correct schema from grade(1).sql
  await conn.execute('DROP TABLE IF EXISTS `Finger_classes`');
  await conn.execute(`
    CREATE TABLE \`Finger_classes\` (
      \`id\`          int          NOT NULL AUTO_INCREMENT,
      \`weight\`      float        DEFAULT NULL,
      \`classes_name\` varchar(255) DEFAULT NULL,
      \`size\`        varchar(50)  DEFAULT NULL,
      \`Farm\`        varchar(255) DEFAULT NULL,
      \`Classes\`     enum('25BCP','30BCP','33BCP','30TR','IF36TR','IF38TR') DEFAULT NULL,
      \`conf\`        float        DEFAULT NULL,
      \`x1\`          float        DEFAULT NULL,
      \`y1\`          float        DEFAULT NULL,
      \`x2\`          float        DEFAULT NULL,
      \`y2\`          float        DEFAULT NULL,
      \`timestamp\`   datetime     DEFAULT CURRENT_TIMESTAMP,
      \`created_at\`  datetime     DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (\`id\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
  `);
  console.log('✅ Finger_classes table created');

  // Read the SQL file and extract INSERT statements
  const sql = fs.readFileSync(
    path.join(__dirname, '..', 'grade(1).sql'), 'utf8'
  );

  // Get all INSERT INTO finger_classes lines
  const insertMatch = sql.match(/INSERT INTO `finger_classes`[^;]+;/gs);
  if (!insertMatch) {
    console.log('❌ No INSERT statements found');
    await conn.end();
    return;
  }

  let total = 0;
  for (const stmt of insertMatch) {
    // Fix table name casing to match our table
    const fixed = stmt.replace('`finger_classes`', '`Finger_classes`');
    await conn.execute(fixed);
    // Count rows by counting value groups
    const count = (fixed.match(/\(/g) || []).length - 1;
    total += count;
    process.stdout.write(`\r  Inserted ~${total} rows...`);
  }

  console.log(`\n✅ Done — ${total} rows imported`);

  const [[{ cnt }]] = await conn.execute('SELECT COUNT(*) AS cnt FROM Finger_classes');
  console.log(`✅ Verified: ${cnt} rows in Finger_classes`);

  await conn.end();
}

run().catch(console.error);
