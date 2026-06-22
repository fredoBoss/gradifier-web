const { getPool } = require('./_lib/db');
const { requireAuth } = require('./_lib/auth');

const GRADES = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];

function validDate(d) {
  return d && /^\d{4}-\d{2}-\d{2}$/.test(d) ? d : null;
}

module.exports = async function handler(req, res) {
  const user = requireAuth(req, res);
  if (!user) return;

  const { start, end, farm, grade } = req.query;

  let startDate = validDate(start);
  let endDate   = validDate(end);
  if (startDate && endDate && startDate > endDate) [startDate, endDate] = [endDate, startDate];

  const farmNum    = farm && /^\d+$/.test(farm) && Number(farm) >= 1 && Number(farm) <= 8 ? farm : null;
  const farmFilter = farmNum ? `Block ${farmNum}` : null;
  const gradeFilter = grade && GRADES.includes(grade) ? grade : null;

  const where = []; const params = [];
  if (startDate)   { where.push('DATE(`timestamp`) >= ?'); params.push(startDate); }
  if (endDate)     { where.push('DATE(`timestamp`) <= ?'); params.push(endDate); }
  if (farmFilter)  { where.push('`Farm` = ?'); params.push(farmFilter); }
  if (gradeFilter) { where.push('`Classes` = ?'); params.push(gradeFilter); }
  const w = where.length ? ' WHERE ' + where.join(' AND ') : '';

  const pool = getPool();
  try {
    const [tableRows] = await pool.execute(
      `SELECT \`Farm\`, DATE_FORMAT(\`timestamp\`, '%Y-%m-%d') AS \`date\`,
        SUM(CASE WHEN \`Classes\`='25BCP'  THEN weight/1000 ELSE 0 END) AS \`25BCP\`,
        SUM(CASE WHEN \`Classes\`='30BCP'  THEN weight/1000 ELSE 0 END) AS \`30BCP\`,
        SUM(CASE WHEN \`Classes\`='33BCP'  THEN weight/1000 ELSE 0 END) AS \`33BCP\`,
        SUM(CASE WHEN \`Classes\`='30TR'   THEN weight/1000 ELSE 0 END) AS \`30TR\`,
        SUM(CASE WHEN \`Classes\`='IF36TR' THEN weight/1000 ELSE 0 END) AS \`IF36TR\`,
        SUM(CASE WHEN \`Classes\`='IF38TR' THEN weight/1000 ELSE 0 END) AS \`IF38TR\`,
        SUM(weight/1000) AS total_weight
        FROM \`Finger_classes\`${w}
        GROUP BY \`Farm\`, DATE(\`timestamp\`) ORDER BY DATE(\`timestamp\`) DESC`,
      params
    );

    const [chartRows] = await pool.execute(
      `SELECT DATE_FORMAT(\`timestamp\`, '%Y-%m-%d') AS \`date\`,
        SUM(CASE WHEN \`Classes\`='25BCP'  THEN weight/1000 ELSE 0 END) AS \`25BCP\`,
        SUM(CASE WHEN \`Classes\`='30BCP'  THEN weight/1000 ELSE 0 END) AS \`30BCP\`,
        SUM(CASE WHEN \`Classes\`='33BCP'  THEN weight/1000 ELSE 0 END) AS \`33BCP\`,
        SUM(CASE WHEN \`Classes\`='30TR'   THEN weight/1000 ELSE 0 END) AS \`30TR\`,
        SUM(CASE WHEN \`Classes\`='IF36TR' THEN weight/1000 ELSE 0 END) AS \`IF36TR\`,
        SUM(CASE WHEN \`Classes\`='IF38TR' THEN weight/1000 ELSE 0 END) AS \`IF38TR\`
        FROM \`Finger_classes\`${w}
        GROUP BY DATE(\`timestamp\`) ORDER BY DATE(\`timestamp\`) ASC`,
      params
    );

    const [dateRows] = await pool.execute(
      `SELECT DISTINCT DATE_FORMAT(\`timestamp\`, '%Y-%m-%d') AS d FROM \`Finger_classes\`${w} ORDER BY d`,
      params
    );

    const [boxRows] = await pool.execute(
      `SELECT \`Classes\`, FLOOR(SUM(\`weight\`/1000)/13.5) AS boxes FROM \`Finger_classes\`${w} GROUP BY \`Classes\``,
      params
    );
    const boxesPerGrade = Object.fromEntries(GRADES.map(g => [g, 0]));
    for (const r of boxRows) {
      if (r.Classes in boxesPerGrade) boxesPerGrade[r.Classes] = Number(r.boxes);
    }

    const [[th]] = await pool.execute(
      `SELECT SUM(\`weight\`/1000) AS kg, COUNT(*) AS cnt FROM \`Finger_classes\`${w}`,
      params
    );

    const totalKg = th.kg ? Math.round(th.kg * 100) / 100 : 0;
    const gradeData = Object.fromEntries(
      GRADES.map(g => [g, chartRows.map(r => Math.round((Number(r[g]) || 0) * 100) / 100)])
    );

    res.json({
      table_rows:          tableRows,
      chart_labels:        chartRows.map(r => r.date),
      grade_data:          gradeData,
      boxes_per_grade:     boxesPerGrade,
      total_harvest_kg:    totalKg,
      total_harvest_count: Number(th.cnt) || 0,
      total_harvest_boxes: totalKg ? Math.floor(totalKg / 13.5) : 0,
      available_dates:     dateRows.map(r => r.d),
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
