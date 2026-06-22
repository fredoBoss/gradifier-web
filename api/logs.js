const { getPool } = require('./_lib/db');
const { requireAuth } = require('./_lib/auth');

const GRADES = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];

function validDate(d) {
  return d && /^\d{4}-\d{2}-\d{2}$/.test(d) ? d : null;
}

module.exports = async function handler(req, res) {
  const user = requireAuth(req, res);
  if (!user) return;

  const { start, end, farm, grade, size, finger } = req.query;

  let startDate = validDate(start);
  let endDate   = validDate(end);
  if (startDate && endDate && startDate > endDate) [startDate, endDate] = [endDate, startDate];

  const farmNum    = farm && /^\d+$/.test(farm) && Number(farm) >= 1 && Number(farm) <= 8 ? farm : null;
  const farmFilter = farmNum ? `Block ${farmNum}` : null;
  const gradeFilter  = grade  && GRADES.includes(grade) ? grade  : null;
  const sizeFilter   = size   && size.trim()   ? size.trim()   : null;
  const fingerFilter = finger && finger.trim() ? finger.trim() : null;

  const where = []; const params = [];
  if (startDate)    { where.push('DATE(`timestamp`) >= ?'); params.push(startDate); }
  if (endDate)      { where.push('DATE(`timestamp`) <= ?'); params.push(endDate); }
  if (farmFilter)   { where.push('`Farm` = ?');             params.push(farmFilter); }
  if (gradeFilter)  { where.push('`Classes` = ?');          params.push(gradeFilter); }
  if (sizeFilter)   { where.push('`size` = ?');             params.push(sizeFilter); }
  if (fingerFilter) { where.push('`classes_name` = ?');     params.push(fingerFilter); }
  const w = where.length ? ' WHERE ' + where.join(' AND ') : '';

  const pool = getPool();
  try {
    const [rows] = await pool.execute(
      `SELECT id, classes_name, size, Farm, Classes, weight, conf,
              DATE_FORMAT(timestamp, '%Y-%m-%d') AS timestamp
       FROM Finger_classes${w} ORDER BY timestamp DESC`,
      params
    );

    const [[th]] = await pool.execute(
      `SELECT SUM(weight/1000) AS kg, COUNT(*) AS cnt FROM Finger_classes${w}`,
      params
    );

    const [dateRows] = await pool.execute(
      'SELECT DISTINCT DATE_FORMAT(timestamp, \'%Y-%m-%d\') AS d FROM Finger_classes ORDER BY d DESC'
    );

    const [sizeRows] = await pool.execute(
      "SELECT DISTINCT size FROM Finger_classes WHERE size IS NOT NULL AND size != '' ORDER BY size"
    );

    const [fingerRows] = await pool.execute(
      "SELECT DISTINCT classes_name FROM Finger_classes WHERE classes_name IS NOT NULL AND classes_name != '' ORDER BY classes_name"
    );

    const totalKg = th.kg ? Math.round(th.kg * 100) / 100 : 0;

    res.json({
      rows,
      total_kg:       totalKg,
      total_count:    Number(th.cnt) || 0,
      total_boxes:    totalKg ? Math.floor(totalKg / 13.5) : 0,
      available_dates:  dateRows.map(r => r.d),
      distinct_sizes:   sizeRows.map(r => r.size),
      distinct_fingers: fingerRows.map(r => r.classes_name),
    });
  } catch (err) {
    console.error(err);
    res.status(500).json({ error: 'Server error' });
  }
};
