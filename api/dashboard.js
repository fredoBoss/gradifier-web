const { getPool } = require('./_lib/db');
const { requireAuth } = require('./_lib/auth');

const GRADES = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];

module.exports = async function handler(req, res) {
  const user = requireAuth(req, res);
  if (!user) return;

  const pool = getPool();
  try {
    const [weightRows] = await pool.execute(
      'SELECT Classes, SUM(weight) AS total_weight FROM Finger_classes WHERE weight >= 0 GROUP BY Classes'
    );
    const classWeights = Object.fromEntries(GRADES.map(g => [g, 0]));
    for (const r of weightRows) {
      if (r.Classes in classWeights)
        classWeights[r.Classes] = Math.round((r.total_weight / 1000) * 100) / 100;
    }

    const [[{ total }]] = await pool.execute(
      'SELECT COUNT(*) AS total FROM Finger_classes WHERE weight >= 0'
    );

    const [[{ latest }]] = await pool.execute(
      'SELECT MAX(timestamp) AS latest FROM Finger_classes WHERE weight >= 0'
    );

    const [boxRows] = await pool.execute(
      'SELECT Classes, FLOOR(SUM(weight / 1000) / 13.5) AS boxes FROM Finger_classes WHERE weight >= 0 GROUP BY Classes'
    );
    const boxesPerGrade = Object.fromEntries(GRADES.map(g => [g, 0]));
    for (const r of boxRows) {
      if (r.Classes in boxesPerGrade) boxesPerGrade[r.Classes] = Number(r.boxes);
    }

    res.json({
      labels: GRADES,
      weights: GRADES.map(g => classWeights[g]),
      total_batches: Number(total),
      latest_update: latest,
      boxes_per_grade: boxesPerGrade,
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
