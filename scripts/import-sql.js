const mysql = require('mysql2/promise');

const DB_URL = process.env.DATABASE_URL;
if (!DB_URL) { console.error('❌ DATABASE_URL env var is not set'); process.exit(1); }

const statements = [

  // ── user ──────────────────────────────────────────────────────────
  `CREATE TABLE IF NOT EXISTS \`user\` (
    \`id\`       int          NOT NULL AUTO_INCREMENT,
    \`email\`    varchar(255) NOT NULL,
    \`password\` varchar(200) NOT NULL,
    \`name\`     varchar(200) NOT NULL,
    \`photo\`    varchar(255) DEFAULT NULL,
    PRIMARY KEY (\`id\`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,

  `INSERT IGNORE INTO \`user\` (\`id\`,\`email\`,\`password\`,\`name\`,\`photo\`) VALUES
   (1,'admin@admin.com','$2y$10$0zGkQLSZ3w.ycp25/E7B5.wlN/tUB.xNacXgkJTqBWL67y5ZVky.6','bimbo','uploads/img_68825d26004fb.png')`,

  // ── login_attempts ────────────────────────────────────────────────
  `CREATE TABLE IF NOT EXISTS \`login_attempts\` (
    \`id\`        int                        NOT NULL AUTO_INCREMENT,
    \`email\`     varchar(255)               NOT NULL,
    \`status\`    enum('success','failed')   NOT NULL,
    \`timestamp\` datetime                   NOT NULL,
    PRIMARY KEY (\`id\`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,

  // ── Finger_classes ────────────────────────────────────────────────
  `CREATE TABLE IF NOT EXISTS \`Finger_classes\` (
    \`id\`          int          NOT NULL AUTO_INCREMENT,
    \`weight\`      float        DEFAULT NULL,
    \`classes_name\` varchar(255) DEFAULT NULL,
    \`size\`        varchar(255) DEFAULT NULL,
    \`Farm\`        varchar(255) DEFAULT NULL,
    \`Classes\`     varchar(255) DEFAULT NULL,
    \`conf\`        float        DEFAULT NULL,
    \`x1\`          float        DEFAULT NULL,
    \`y1\`          float        DEFAULT NULL,
    \`x2\`          float        DEFAULT NULL,
    \`y2\`          float        DEFAULT NULL,
    \`timestamp\`   datetime     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (\`id\`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4`,
];

(async () => {
  const conn = await mysql.createConnection({
    uri: DB_URL,
    ssl: { rejectUnauthorized: false },
  });
  console.log('✅ Connected to Aiven MySQL\n');

  for (const sql of statements) {
    try {
      await conn.execute(sql);
      const label = sql.trim().split('\n')[0].slice(0, 60);
      console.log(`✅ ${label}`);
    } catch (e) {
      console.error(`❌ ${e.message}`);
    }
  }

  console.log('\n── Tables ──');
  const [tables] = await conn.execute('SHOW TABLES');
  tables.forEach(t => console.log(' -', Object.values(t)[0]));

  await conn.end();
  console.log('\nDone. Now import your Finger_classes data using grade.sql manually if needed.');
})();
