<?php
require_once 'config.php';
require_once '../php/auth.php';
requireLogin();

if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Validates a Y-m-d date string and returns it, or null if invalid.
function gd_valid_date($d) {
  if ($d === '' || $d === null) return null;
  $dt = DateTime::createFromFormat('Y-m-d', $d);
  return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
}
$startDate = gd_valid_date(isset($_GET['start']) ? trim($_GET['start']) : '');
$endDate   = gd_valid_date(isset($_GET['end'])   ? trim($_GET['end'])   : '');
// Backward compatibility with the old single ?date= param
if (!$startDate && !$endDate) {
  $legacy = gd_valid_date(isset($_GET['date']) ? trim($_GET['date']) : '');
  if ($legacy) { $startDate = $endDate = $legacy; }
}
// Normalise order (swap if start is after end)
if ($startDate && $endDate && $startDate > $endDate) { $tmp = $startDate; $startDate = $endDate; $endDate = $tmp; }
$selectedStart = $startDate ?? '';
$selectedEnd   = $endDate   ?? '';
$selectedRange = isset($_GET['range']) ? trim($_GET['range']) : '';
$farmFilter = (isset($_GET['farm']) && is_numeric($_GET['farm']) && $_GET['farm'] >= 1 && $_GET['farm'] <= 8)
  ? 'Block ' . (int)$_GET['farm'] : null;
$selectedFarm = isset($_GET['farm']) ? (int)$_GET['farm'] : '';

$validGrades  = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];
$gradeFilter  = (isset($_GET['grade']) && in_array($_GET['grade'], $validGrades, true)) ? $_GET['grade'] : null;
$selectedGrade = $gradeFilter ?? '';

// Table query (Farm + Date grouping, DESC for display)
$sql = "SELECT `Farm`, DATE(`timestamp`) AS `date`,
        SUM(CASE WHEN `Classes` = '25BCP'  THEN weight / 1000 ELSE 0 END) AS `25BCP`,
        SUM(CASE WHEN `Classes` = '30BCP'  THEN weight / 1000 ELSE 0 END) AS `30BCP`,
        SUM(CASE WHEN `Classes` = '33BCP'  THEN weight / 1000 ELSE 0 END) AS `33BCP`,
        SUM(CASE WHEN `Classes` = '30TR'   THEN weight / 1000 ELSE 0 END) AS `30TR`,
        SUM(CASE WHEN `Classes` = 'IF36TR' THEN weight / 1000 ELSE 0 END) AS `IF36TR`,
        SUM(CASE WHEN `Classes` = 'IF38TR' THEN weight / 1000 ELSE 0 END) AS `IF38TR`,
        SUM(weight / 1000) AS `total_weight`
        FROM `Finger_classes`";

$whereClauses = []; $params = []; $types = '';
if ($startDate)   { $whereClauses[] = "DATE(`timestamp`) >= ?"; $params[] = $startDate; $types .= 's'; }
if ($endDate)     { $whereClauses[] = "DATE(`timestamp`) <= ?"; $params[] = $endDate;   $types .= 's'; }
if ($farmFilter)  { $whereClauses[] = "`Farm` = ?";            $params[] = $farmFilter;  $types .= 's'; }
if ($gradeFilter) { $whereClauses[] = "`Classes` = ?";         $params[] = $gradeFilter; $types .= 's'; }
if (!empty($whereClauses)) $sql .= " WHERE " . implode(" AND ", $whereClauses);
$sql .= " GROUP BY `Farm`, DATE(`timestamp`) ORDER BY DATE(`timestamp`) DESC";

$stmt = $conn->prepare($sql);
if ($stmt) {
  if (!empty($params)) $stmt->bind_param($types, ...$params);
  $stmt->execute();
  $result = $stmt->get_result();
  $stmt->close();
} else { $result = false; }

// Chart query — grouped by date only, ASC for chronological display
$chartSql = "SELECT DATE(`timestamp`) AS `date`,
        SUM(CASE WHEN `Classes` = '25BCP'  THEN weight / 1000 ELSE 0 END) AS `25BCP`,
        SUM(CASE WHEN `Classes` = '30BCP'  THEN weight / 1000 ELSE 0 END) AS `30BCP`,
        SUM(CASE WHEN `Classes` = '33BCP'  THEN weight / 1000 ELSE 0 END) AS `33BCP`,
        SUM(CASE WHEN `Classes` = '30TR'   THEN weight / 1000 ELSE 0 END) AS `30TR`,
        SUM(CASE WHEN `Classes` = 'IF36TR' THEN weight / 1000 ELSE 0 END) AS `IF36TR`,
        SUM(CASE WHEN `Classes` = 'IF38TR' THEN weight / 1000 ELSE 0 END) AS `IF38TR`
        FROM `Finger_classes`";
$cWhere = []; $cParams = []; $cTypes = '';
if ($startDate)   { $cWhere[] = "DATE(`timestamp`) >= ?"; $cParams[] = $startDate; $cTypes .= 's'; }
if ($endDate)     { $cWhere[] = "DATE(`timestamp`) <= ?"; $cParams[] = $endDate;   $cTypes .= 's'; }
if ($farmFilter)  { $cWhere[] = "`Farm` = ?";            $cParams[] = $farmFilter;  $cTypes .= 's'; }
if ($gradeFilter) { $cWhere[] = "`Classes` = ?";         $cParams[] = $gradeFilter; $cTypes .= 's'; }
if (!empty($cWhere)) $chartSql .= " WHERE " . implode(" AND ", $cWhere);
$chartSql .= " GROUP BY DATE(`timestamp`) ORDER BY DATE(`timestamp`) ASC";

$chartRows = [];
$cStmt = $conn->prepare($chartSql);
if ($cStmt) {
  if (!empty($cParams)) $cStmt->bind_param($cTypes, ...$cParams);
  $cStmt->execute();
  $cResult = $cStmt->get_result();
  while ($r = $cResult->fetch_assoc()) $chartRows[] = $r;
  $cStmt->close();
}

// Dates with records for calendar highlighting
$datesSql = "SELECT DISTINCT DATE(`timestamp`) AS d FROM `Finger_classes`";
$dWhere = []; $dParams = []; $dTypes = '';
if ($farmFilter)  { $dWhere[] = "`Farm` = ?";    $dParams[] = $farmFilter;  $dTypes .= 's'; }
if ($gradeFilter) { $dWhere[] = "`Classes` = ?"; $dParams[] = $gradeFilter; $dTypes .= 's'; }
if (!empty($dWhere)) $datesSql .= " WHERE " . implode(" AND ", $dWhere);
$availableDates = [];
$datesStmt = $conn->prepare($datesSql);
if ($datesStmt) {
  if (!empty($dParams)) $datesStmt->bind_param($dTypes, ...$dParams);
  $datesStmt->execute();
  $dr = $datesStmt->get_result();
  while ($r = $dr->fetch_assoc()) $availableDates[] = $r['d'];
  $datesStmt->close();
}

// Collect table rows
$tableRows = [];
if ($result) { while ($row = $result->fetch_assoc()) $tableRows[] = $row; }

$grades = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];

// Boxes per grade (13.5 kg per box)
$boxSql = "SELECT `Classes`, FLOOR(SUM(`weight` / 1000) / 13.5) AS `boxes` FROM `Finger_classes`";
$bWhere = []; $bParams = []; $bTypes = '';
if ($startDate)   { $bWhere[] = "DATE(`timestamp`) >= ?"; $bParams[] = $startDate; $bTypes .= 's'; }
if ($endDate)     { $bWhere[] = "DATE(`timestamp`) <= ?"; $bParams[] = $endDate;   $bTypes .= 's'; }
if ($farmFilter)  { $bWhere[] = "`Farm` = ?";            $bParams[] = $farmFilter;  $bTypes .= 's'; }
if ($gradeFilter) { $bWhere[] = "`Classes` = ?";         $bParams[] = $gradeFilter; $bTypes .= 's'; }
if (!empty($bWhere)) $boxSql .= " WHERE " . implode(" AND ", $bWhere);
$boxSql .= " GROUP BY `Classes`";
$boxesPerGrade = array_fill_keys($grades, 0);
$bStmt = $conn->prepare($boxSql);
if ($bStmt) {
  if (!empty($bParams)) $bStmt->bind_param($bTypes, ...$bParams);
  $bStmt->execute();
  $bResult = $bStmt->get_result();
  while ($r = $bResult->fetch_assoc()) {
    if (isset($boxesPerGrade[$r['Classes']])) $boxesPerGrade[$r['Classes']] = (int)$r['boxes'];
  }
  $bStmt->close();
}

// Total harvest (kg) + record count for the current filters / range
$thSql = "SELECT SUM(`weight` / 1000) AS `kg`, COUNT(*) AS `cnt` FROM `Finger_classes`";
$thWhere = []; $thParams = []; $thTypes = '';
if ($startDate)   { $thWhere[] = "DATE(`timestamp`) >= ?"; $thParams[] = $startDate;  $thTypes .= 's'; }
if ($endDate)     { $thWhere[] = "DATE(`timestamp`) <= ?"; $thParams[] = $endDate;    $thTypes .= 's'; }
if ($farmFilter)  { $thWhere[] = "`Farm` = ?";             $thParams[] = $farmFilter; $thTypes .= 's'; }
if ($gradeFilter) { $thWhere[] = "`Classes` = ?";          $thParams[] = $gradeFilter;$thTypes .= 's'; }
if (!empty($thWhere)) $thSql .= " WHERE " . implode(" AND ", $thWhere);
$totalHarvestKg = 0.0; $totalHarvestCount = 0;
$thStmt = $conn->prepare($thSql);
if ($thStmt) {
  if (!empty($thParams)) $thStmt->bind_param($thTypes, ...$thParams);
  $thStmt->execute();
  $thRes = $thStmt->get_result();
  if ($thRow = $thRes->fetch_assoc()) {
    $totalHarvestKg    = (float)($thRow['kg']  ?? 0);
    $totalHarvestCount = (int)  ($thRow['cnt'] ?? 0);
  }
  $thStmt->close();
}
$totalHarvestBoxes = (int)floor($totalHarvestKg / 13.5);

$conn->close();

$hasFilters = $selectedFarm || $selectedStart || $selectedEnd || $selectedGrade;

// Build chart JSON
$chartLabels = array_column($chartRows, 'date');
$gradeData   = [];
foreach ($grades as $g) {
  $gradeData[$g] = array_map(fn($r) => round((float)$r[$g], 2), $chartRows);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reports — Gradifier</title>
  <?php include '../php/pwa_head.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../src/styles.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/hammer.js/2.0.8/hammer.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.1/dist/chartjs-plugin-zoom.min.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Poppins:wght@300;400;500;600&display=swap');
    * { font-family: 'Poppins', sans-serif; }
    .font-brand { font-family: 'Montserrat', sans-serif; }
    select { appearance: none; -webkit-appearance: none; }
    tbody tr:hover { background: #f0fdf4; }
    .flatpickr-day.selected, .flatpickr-day.selected:hover,
    .flatpickr-day.startRange, .flatpickr-day.startRange:hover,
    .flatpickr-day.endRange, .flatpickr-day.endRange:hover { background: #059669 !important; border-color: #059669 !important; color: #fff !important; }
    .flatpickr-day.inRange { background: #d1fae5 !important; border-color: transparent !important; box-shadow: -5px 0 0 #d1fae5, 5px 0 0 #d1fae5 !important; }
    .flatpickr-day.has-harvest:not(.selected):not(.startRange):not(.endRange) { background: #bbf7d0 !important; border-color: #34d399 !important; color: #065f46 !important; font-weight: 600 !important; }
    .flatpickr-day.has-harvest.inRange { background: #6ee7b7 !important; box-shadow: -5px 0 0 #6ee7b7, 5px 0 0 #6ee7b7 !important; }
    .flatpickr-day:hover:not(.selected):not(.startRange):not(.endRange):not(.has-harvest) { background: #d1fae5 !important; }
    .flatpickr-day.has-harvest:not(.selected):not(.startRange):not(.endRange):hover { background: #6ee7b7 !important; border-color: #10b981 !important; }
    .flatpickr-day.today:not(.selected):not(.startRange):not(.endRange) { border-color: #10b981 !important; }
    .flatpickr-input { background: #f9fafb; border: 1px solid #e5e7eb; color: #374151; font-size: .875rem; border-radius: .75rem; padding: .5rem .75rem; width: 100%; outline: none; cursor: pointer; }
    .flatpickr-input:focus { border-color: #34d399; box-shadow: 0 0 0 1px #d1fae5; }
    .view-tab { transition: all .2s; }
    .view-tab.active { background: #059669; color: #fff; border-color: #059669; }
    .view-tab:not(.active) { color: #6b7280; background: #fff; }
    .view-tab:not(.active):hover { border-color: #6ee7b7; color: #059669; }
    .chart-scroll-container { scrollbar-width: thin; scrollbar-color: #6ee7b7 #f3f4f6; }
    .chart-scroll-container::-webkit-scrollbar { height: 6px; }
    .chart-scroll-container::-webkit-scrollbar-track { background: #f3f4f6; border-radius: 9999px; }
    .chart-scroll-container::-webkit-scrollbar-thumb { background: #6ee7b7; border-radius: 9999px; }
    .chart-scroll-container::-webkit-scrollbar-thumb:hover { background: #059669; }
  </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

  <div id="header-placeholder" class="sticky top-0 z-30"></div>

  <div class="flex flex-1">
    <div id="sidebar-placeholder" class="flex-shrink-0"></div>

    <main class="flex-1 p-6 overflow-x-hidden min-w-0">

      <!-- Page Title -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-800 font-brand">Reports</h1>
          <p class="text-sm text-gray-500 mt-0.5">Weight summary by grade class over time</p>
        </div>
        <button onclick="exportToPDF2()"
          class="flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-sm transition-all"
          style="background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 14px rgba(16,185,129,0.35);"
          onmouseover="this.style.boxShadow='0 6px 20px rgba(16,185,129,0.5)';this.style.transform='translateY(-1px)'"
          onmouseout="this.style.boxShadow='0 4px 14px rgba(16,185,129,0.35)';this.style.transform=''">
          <i class="fa-solid fa-file-pdf"></i>
          Export PDF
        </button>
      </div>

      <!-- Filter Bar -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 mb-6">
        <form method="get" id="filterForm" class="flex flex-wrap items-end gap-4">

          <!-- Block Filter -->
          <div class="flex flex-col gap-1 min-w-[150px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Block</label>
            <div class="relative">
              <select name="farm" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All Blocks</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                  <option value="<?= $i ?>" <?= $selectedFarm == $i ? 'selected' : '' ?>>Block <?= $i ?></option>
                <?php endfor; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Grade Filter -->
          <div class="flex flex-col gap-1 min-w-[150px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Grade</label>
            <div class="relative">
              <select name="grade" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All Grades</option>
                <?php foreach ($validGrades as $g): ?>
                  <option value="<?= $g ?>" <?= $selectedGrade === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Quick Range Preset -->
          <div class="flex flex-col gap-1 min-w-[150px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Quick Range</label>
            <div class="relative">
              <select name="range" id="rangeSelect" onchange="applyQuickRange(this.value)"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">Custom</option>
                <option value="this_week"  <?= $selectedRange === 'this_week'  ? 'selected' : '' ?>>This Week</option>
                <option value="last_week"  <?= $selectedRange === 'last_week'  ? 'selected' : '' ?>>Last Week</option>
                <option value="this_month" <?= $selectedRange === 'this_month' ? 'selected' : '' ?>>This Month</option>
                <option value="last_month" <?= $selectedRange === 'last_month' ? 'selected' : '' ?>>Last Month</option>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Date Range Filter (two dates) -->
          <div class="flex flex-col gap-1 min-w-[210px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Date Range</label>
            <input type="text" id="rangePicker" placeholder="Pick start &amp; end dates" readonly="readonly" />
            <input type="hidden" name="start" id="startDate" value="<?= htmlspecialchars($selectedStart) ?>" />
            <input type="hidden" name="end"   id="endDate"   value="<?= htmlspecialchars($selectedEnd) ?>" />
          </div>

          <!-- Active filter chips + clear -->
          <?php if ($hasFilters): ?>
            <div class="flex items-center gap-2 flex-wrap pb-0.5">
              <?php if ($selectedFarm): ?>
                <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-tractor text-xs"></i> block <?= $selectedFarm ?>
                </span>
              <?php endif; ?>
              <?php if ($selectedGrade): ?>
                <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-tag text-xs"></i> <?= htmlspecialchars($selectedGrade) ?>
                </span>
              <?php endif; ?>
              <?php if ($selectedStart || $selectedEnd): ?>
                <span class="text-xs bg-emerald-50 text-emerald-700 border border-emerald-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-calendar text-xs"></i>
                  <?php if ($selectedStart && $selectedEnd && $selectedStart === $selectedEnd): ?>
                    <?= htmlspecialchars($selectedStart) ?>
                  <?php else: ?>
                    <?= htmlspecialchars($selectedStart ?: '…') ?> → <?= htmlspecialchars($selectedEnd ?: '…') ?>
                  <?php endif; ?>
                </span>
              <?php endif; ?>
              <a href="reports.php"
                class="text-xs text-gray-400 hover:text-red-500 flex items-center gap-1 no-underline transition-colors">
                <i class="fa-solid fa-xmark"></i> Clear
              </a>
            </div>
          <?php endif; ?>

        </form>
      </div>

      <!-- Total Harvest Summary -->
      <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-4 mb-4 flex flex-wrap items-center justify-between gap-4"
           style="background:linear-gradient(135deg,#ecfdf5,#ffffff);">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white flex-shrink-0"
               style="background:linear-gradient(135deg,#10b981,#059669);">
            <i class="fa-solid fa-weight-hanging"></i>
          </div>
          <div>
            <span class="text-[11px] text-gray-400 font-medium uppercase tracking-wider block">
              Total Harvest<?= ($selectedStart || $selectedEnd) ? ' (selected range)' : '' ?>
            </span>
            <span class="text-2xl font-bold text-gray-800">
              <?= number_format($totalHarvestKg, 2) ?> <span class="text-sm font-medium text-gray-400">kg</span>
            </span>
          </div>
        </div>
        <div class="flex items-center gap-6">
          <div class="text-center">
            <span class="text-lg font-bold text-emerald-700 block"><?= number_format($totalHarvestBoxes) ?></span>
            <span class="text-[10px] text-gray-400 uppercase tracking-wider">boxes</span>
          </div>
          <div class="text-center">
            <span class="text-lg font-bold text-gray-700 block"><?= number_format($totalHarvestCount) ?></span>
            <span class="text-[10px] text-gray-400 uppercase tracking-wider">records</span>
          </div>
          <?php if ($selectedStart || $selectedEnd): ?>
          <div class="text-center">
            <span class="text-sm font-semibold text-gray-600 block whitespace-nowrap">
              <?= htmlspecialchars($selectedStart ?: '…') ?> → <?= htmlspecialchars($selectedEnd ?: '…') ?>
            </span>
            <span class="text-[10px] text-gray-400 uppercase tracking-wider">range</span>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Boxes per Grade Summary -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <?php
        $gradeColors = [
          '25BCP'  => ['border'=>'border-blue-200',   'bg'=>'bg-blue-50',   'text'=>'text-blue-700',   'icon'=>'text-blue-400'],
          '30BCP'  => ['border'=>'border-orange-200', 'bg'=>'bg-orange-50', 'text'=>'text-orange-700', 'icon'=>'text-orange-400'],
          '33BCP'  => ['border'=>'border-emerald-200','bg'=>'bg-emerald-50','text'=>'text-emerald-700','icon'=>'text-emerald-400'],
          '30TR'   => ['border'=>'border-purple-200', 'bg'=>'bg-purple-50', 'text'=>'text-purple-700', 'icon'=>'text-purple-400'],
          'IF36TR' => ['border'=>'border-red-200',    'bg'=>'bg-red-50',    'text'=>'text-red-700',    'icon'=>'text-red-400'],
          'IF38TR' => ['border'=>'border-yellow-200', 'bg'=>'bg-yellow-50', 'text'=>'text-yellow-700', 'icon'=>'text-yellow-400'],
        ];
        foreach ($grades as $g):
          $c = $gradeColors[$g];
          $boxes = $boxesPerGrade[$g];
        ?>
        <div class="bg-white rounded-2xl border <?= $c['border'] ?> shadow-sm p-4 flex flex-col gap-1">
          <span class="text-[11px] font-semibold uppercase tracking-wider <?= $c['text'] ?>"><?= $g ?></span>
          <div class="flex items-end gap-1.5 mt-1">
            <span class="text-2xl font-bold text-gray-800"><?= number_format($boxes) ?></span>
            <span class="text-xs <?= $c['icon'] ?> mb-0.5">boxes</span>
          </div>
          <span class="text-[10px] text-gray-400">@ 13.5 kg/box</span>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- View Toggle -->
      <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
        <div class="flex items-center gap-2">
          <button id="tabChart" onclick="switchView('chart')"
            class="view-tab active text-xs font-semibold px-4 py-1.5 rounded-lg border border-gray-200 flex items-center gap-1.5">
            <i class="fa-solid fa-chart-line text-xs"></i> Chart
          </button>
          <button id="tabTable" onclick="switchView('table')"
            class="view-tab text-xs font-semibold px-4 py-1.5 rounded-lg border border-gray-200 flex items-center gap-1.5">
            <i class="fa-solid fa-table text-xs"></i> Table
          </button>
        </div>
        <div id="chartTypeToggle" class="flex items-center gap-2">
          <button id="typeLine" onclick="switchChartType('line')"
            class="view-tab active text-xs font-semibold px-4 py-1.5 rounded-lg border border-gray-200 flex items-center gap-1.5">
            <i class="fa-solid fa-chart-line text-xs"></i> Line
          </button>
          <button id="typeBar" onclick="switchChartType('bar')"
            class="view-tab text-xs font-semibold px-4 py-1.5 rounded-lg border border-gray-200 flex items-center gap-1.5">
            <i class="fa-solid fa-chart-bar text-xs"></i> Bar
          </button>
          <button id="typeMixed" onclick="switchChartType('mixed')"
            class="view-tab text-xs font-semibold px-4 py-1.5 rounded-lg border border-gray-200 flex items-center gap-1.5">
            <i class="fa-solid fa-chart-mixed text-xs"></i> Mixed
          </button>
        </div>
      </div>

      <!-- Chart Card -->
      <div id="chartView" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-6">
        <?php if (empty($chartRows)): ?>
          <div class="flex flex-col items-center justify-center py-16">
            <i class="fa-solid fa-chart-line text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-400 text-sm font-medium">No data to display<?= $hasFilters ? ' for the selected filters' : '' ?>.</p>
            <?php if ($hasFilters): ?>
              <a href="reports.php" class="text-emerald-600 text-xs mt-1 hover:underline">Clear filters</a>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-gray-400 flex items-center gap-1.5">
              <i class="fa-solid fa-magnifying-glass-plus"></i>
              Scroll to zoom &bull; Drag to pan
            </span>
            <button id="resetZoomBtn" onclick="resetZoom()"
              class="text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:border-emerald-300 hover:text-emerald-600 transition-colors flex items-center gap-1.5">
              <i class="fa-solid fa-arrows-rotate text-xs"></i> Reset Zoom
            </button>
          </div>
          <div class="overflow-x-auto rounded-xl chart-scroll-container">
            <div id="chartWrapper" style="height:380px; min-width:100%;">
              <canvas id="gradesChart"></canvas>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Table Card (hidden by default, used for PDF export) -->
      <div id="tableView" class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden" style="display:none;">
        <div class="overflow-x-auto">
          <table class="min-w-full" id="dataTable">
            <thead>
              <tr style="background:#f0fdf4; border-bottom:2px solid #d1fae5;">
                <?php
                $headers = ['No.', 'Farm', 'Date', '25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR', 'Total (kg)', 'Boxes'];
                foreach ($headers as $h): ?>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider" style="color:#065f46;">
                    <?= $h ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php
              $no = 1;
              $hasRows = !empty($tableRows);
              foreach ($tableRows as $row):
                $isEven = $no % 2 === 0;
              ?>
                <tr class="transition-colors" style="<?= $isEven ? 'background:#fafafa;' : '' ?>">
                  <td class="px-4 py-3 text-xs text-gray-400 font-medium"><?= $no++ ?></td>
                  <td class="px-4 py-3 text-sm font-semibold text-gray-700">
                    <?= htmlspecialchars($row['Farm'] ?? 'N/A') ?>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600">
                    <?= htmlspecialchars($row['date'] ?? 'N/A') ?>
                  </td>
                  <?php
                  foreach ($grades as $g):
                    $val = isset($row[$g]) ? number_format($row[$g], 2) : '0.00';
                  ?>
                  <td class="px-4 py-3 text-sm text-gray-700"><?= $val ?> <span class="text-gray-400 text-xs">kg</span></td>
                  <?php endforeach; ?>
                  <td class="px-4 py-3 text-sm font-semibold text-emerald-700">
                    <?= isset($row['total_weight']) ? number_format($row['total_weight'], 2) : '0.00' ?>
                    <span class="text-emerald-500 text-xs">kg</span>
                  </td>
                  <td class="px-4 py-3 text-sm font-semibold text-gray-700">
                    <?= isset($row['total_weight']) ? number_format(floor($row['total_weight'] / 13.5)) : '0' ?>
                    <span class="text-gray-400 text-xs">boxes</span>
                  </td>
                </tr>
              <?php endforeach; ?>

              <?php if (!$hasRows): ?>
                <tr>
                  <td colspan="11" class="px-4 py-16 text-center">
                    <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-3 block"></i>
                    <?php if ($selectedStart || $selectedEnd): ?>
                      <p class="text-gray-400 text-sm font-medium">No harvest in this range.</p>
                    <?php else: ?>
                      <p class="text-gray-400 text-sm">No records found<?= $hasFilters ? ' for the selected filters' : '' ?>.</p>
                    <?php endif; ?>
                    <?php if ($hasFilters): ?>
                      <a href="reports.php" class="text-emerald-600 text-xs mt-1 inline-block hover:underline">Clear filters</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div class="flex items-center justify-between px-5 py-3 border-t border-gray-100">
          <span id="pageInfo" class="text-xs text-gray-400">Page 1</span>
          <div class="flex items-center gap-2">
            <button id="prevPage"
              class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:border-emerald-300 hover:text-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
              <i class="fa-solid fa-chevron-left text-xs"></i> Prev
            </button>
            <button id="nextPage"
              class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-lg border border-gray-200 text-gray-500 hover:border-emerald-300 hover:text-emerald-600 disabled:opacity-40 disabled:cursor-not-allowed transition-colors">
              Next <i class="fa-solid fa-chevron-right text-xs"></i>
            </button>
          </div>
        </div>
      </div>

    </main>
  </div>

  <script>
    $(function () {
      $("#header-placeholder").load("header.php");
      $("#sidebar-placeholder").load("sidebar.html");
    });

    // Switches between the chart and table display panels.
    function switchView(view) {
      const isChart = view === 'chart';
      document.getElementById('chartView').style.display = isChart ? '' : 'none';
      document.getElementById('tableView').style.display = isChart ? 'none' : '';
      document.getElementById('tabChart').classList.toggle('active', isChart);
      document.getElementById('tabTable').classList.toggle('active', !isChart);
      document.getElementById('chartTypeToggle').style.display = isChart ? '' : 'none';
    }

    // Activates the selected chart type tab and rebuilds the chart.
    let activeChartType = 'line';
    function switchChartType(type) {
      activeChartType = type;
      document.getElementById('typeLine').classList.toggle('active', type === 'line');
      document.getElementById('typeBar').classList.toggle('active', type === 'bar');
      document.getElementById('typeMixed').classList.toggle('active', type === 'mixed');
      buildChart(type);
    }

    // Pagination
    document.addEventListener("DOMContentLoaded", function () {
      const rows = document.querySelectorAll("tbody tr");
      const rowsPerPage = 8;
      let currentPage = 1;
      const totalPages = Math.ceil(rows.length / rowsPerPage) || 1;
      const pageInfo = document.getElementById("pageInfo");
      const prevBtn  = document.getElementById("prevPage");
      const nextBtn  = document.getElementById("nextPage");

      // Shows only the rows for the given page and updates pagination controls.
      function displayRows(page) {
        const start = (page - 1) * rowsPerPage;
        rows.forEach((row, i) => {
          row.style.display = (i >= start && i < start + rowsPerPage) ? "" : "none";
        });
        if (pageInfo) pageInfo.textContent = `Page ${page} of ${totalPages}`;
        if (prevBtn) prevBtn.disabled = page === 1;
        if (nextBtn) nextBtn.disabled = page === totalPages;
      }

      if (prevBtn) prevBtn.addEventListener("click", () => { if (currentPage > 1) displayRows(--currentPage); });
      if (nextBtn) nextBtn.addEventListener("click", () => { if (currentPage < totalPages) displayRows(++currentPage); });
      displayRows(1);
    });

    // Exports all table rows to a formatted PDF file using jsPDF.
    function exportToPDF2() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const now = new Date();
      const dateStr = now.toISOString().slice(0, 10);
      const pageWidth = doc.internal.pageSize.getWidth();

      doc.setFontSize(10);
      doc.setFont("helvetica", "normal");
      const tsText = now.toLocaleString();
      doc.text(tsText, pageWidth - doc.getTextWidth(tsText) - 14, 10);

      const title = "Block Report Record (Weights in Kilograms)";
      doc.setFontSize(16);
      doc.setFont("helvetica", "bold");
      doc.text(title, (pageWidth - doc.getTextWidth(title)) / 2, 16);

      // Collect all table rows (show hidden table temporarily for DOM access)
      const tv = document.getElementById('tableView');
      const wasHidden = tv.style.display === 'none';
      if (wasHidden) tv.style.display = '';

      const pdfRows = [];
      document.querySelectorAll("#dataTable tbody tr").forEach(tr => {
        const rowData = [];
        tr.querySelectorAll("td").forEach(td => rowData.push(td.innerText.trim()));
        if (rowData.length > 1) pdfRows.push(rowData);
      });

      if (wasHidden) tv.style.display = 'none';

      doc.autoTable({
        head: [["No", "Block", "Date", "25BCP (kg)", "30BCP (kg)", "33BCP (kg)", "30TR (kg)", "IF36TR (kg)", "IF38TR (kg)", "Total (kg)", "Boxes"]],
        body: pdfRows,
        startY: 24,
        theme: 'grid',
        headStyles: { fillColor: [6, 95, 70], textColor: 255, fontStyle: 'bold', halign: 'center' },
        styles: { halign: 'center', fontSize: 9 },
      });

      doc.save(`Reports_${dateStr}.pdf`);
    }
  </script>

  <!-- Chart.js initialisation -->
  <script>
    const chartLabels    = <?= json_encode($chartLabels) ?>;
    const gradeData      = <?= json_encode($gradeData) ?>;
    const availableDates = <?= json_encode($availableDates) ?>;
    const activeGrade    = <?= json_encode($selectedGrade ?: null) ?>;
    const selStart       = <?= json_encode($selectedStart ?: null) ?>;
    const selEnd         = <?= json_encode($selectedEnd ?: null) ?>;

    const palette = {
      '25BCP':  { border: '#3b82f6', bg: 'rgba(59,130,246,0.08)'  },
      '30BCP':  { border: '#f97316', bg: 'rgba(249,115,22,0.08)'  },
      '33BCP':  { border: '#10b981', bg: 'rgba(16,185,129,0.08)'  },
      '30TR':   { border: '#8b5cf6', bg: 'rgba(139,92,246,0.08)'  },
      'IF36TR': { border: '#ef4444', bg: 'rgba(239,68,68,0.08)'   },
      'IF38TR': { border: '#eab308', bg: 'rgba(234,179,8,0.08)'   },
    };

    let chartInstance = null;

    // Destroys any existing chart and builds a new one with the given type (line/bar/mixed).
    function buildChart(type) {
      if (chartInstance) { chartInstance.destroy(); chartInstance = null; }
      if (!chartLabels.length) return;

      const isBar    = type === 'bar';
      const isMixed  = type === 'mixed';
      // Bars are grouped (separated) per grade/class in both Bar and Mixed modes; Mixed overlays a Total line on top.
      const stacked  = false;
      const visibleGrades = activeGrade ? [activeGrade] : Object.keys(gradeData);

      const datasets = visibleGrades.map(label => ({
        type: isMixed ? 'bar' : type,
        label,
        data: gradeData[label],
        borderColor:     palette[label]?.border ?? '#6b7280',
        backgroundColor: (isBar || isMixed) ? (palette[label]?.border ?? '#6b7280') + 'cc' : (palette[label]?.bg ?? 'transparent'),
        borderWidth: (isBar || isMixed) ? 0 : 2.5,
        borderRadius: (isBar || isMixed) ? 4 : 0,
        pointRadius: (isBar || isMixed) ? 0 : 4,
        pointHoverRadius: (isBar || isMixed) ? 0 : 6,
        pointBackgroundColor: palette[label]?.border ?? '#6b7280',
        pointBorderColor: '#fff',
        pointBorderWidth: 2,
        tension: 0.35,
        fill: false,
        stack: stacked ? 'grades' : undefined,
      }));

      if (isMixed) {
        const totals = chartLabels.map((_, i) =>
          visibleGrades.reduce((sum, g) => sum + (gradeData[g][i] ?? 0), 0)
        );
        datasets.push({
          type: 'line',
          label: 'Total',
          data: totals,
          borderColor: '#064e3b',
          backgroundColor: 'transparent',
          borderWidth: 2.5,
          pointRadius: 4,
          pointHoverRadius: 6,
          pointBackgroundColor: '#064e3b',
          pointBorderColor: '#fff',
          pointBorderWidth: 2,
          tension: 0.35,
          fill: false,
          stack: undefined,
        });
      }

      // Grouped bars need more room per date so the separate grade bars stay readable.
      const PX_PER_POINT = (isBar || isMixed) ? 90 : 55;
      const chartWrapper = document.getElementById('chartWrapper');
      if (chartWrapper) {
        const dynamicWidth = Math.max(chartLabels.length * PX_PER_POINT, 600);
        chartWrapper.style.width = dynamicWidth + 'px';
      }

      chartInstance = new Chart(document.getElementById('gradesChart'), {
        type: isMixed ? 'bar' : type,
        data: { labels: chartLabels, datasets },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                usePointStyle: true,
                pointStyle: (isBar || isMixed) ? 'rect' : 'rectRounded',
                padding: 20,
                font: { family: 'Poppins', size: 12 },
                color: '#374151',
              },
            },
            tooltip: {
              backgroundColor: '#fff',
              borderColor: '#e5e7eb',
              borderWidth: 1,
              titleColor: '#111827',
              bodyColor: '#374151',
              padding: 12,
              callbacks: {
                label: ctx => ` ${ctx.dataset.label}: ${ctx.parsed.y.toFixed(2)} kg`,
              },
            },
            zoom: {
              zoom: {
                wheel: { enabled: true, speed: 0.1 },
                pinch: { enabled: true },
                mode: 'x',
              },
              pan: {
                enabled: true,
                mode: 'x',
              },
              limits: {
                x: { minRange: 2 },
              },
            },
          },
          scales: {
            x: {
              stacked,
              grid: { color: '#f3f4f6', drawBorder: false },
              ticks: { font: { family: 'Poppins', size: 11 }, color: '#9ca3af', maxRotation: 45 },
              border: { display: false },
            },
            y: {
              stacked,
              grid: { color: '#f3f4f6', drawBorder: false },
              ticks: { font: { family: 'Poppins', size: 11 }, color: '#9ca3af', callback: v => v + ' kg' },
              border: { display: false },
            },
          },
        },
      });
    }

    // Resets chart zoom/pan to the default view.
    function resetZoom() {
      if (chartInstance) chartInstance.resetZoom();
    }

    buildChart('line');

    // Formats a Date object as a YYYY-MM-DD string.
    function fmtDate(d) {
      return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
    }

    const rangeDefault = (selStart && selEnd) ? [selStart, selEnd] : (selStart ? [selStart] : null);

    flatpickr("#rangePicker", {
      mode: "range",
      dateFormat: "Y-m-d",
      maxDate: "today",
      defaultDate: rangeDefault,
      onDayCreate: function(dObj, dStr, fp, dayElem) {
        if (availableDates.includes(fmtDate(dayElem.dateObj))) {
          dayElem.classList.add('has-harvest');
        }
      },
      onClose: function(selectedDates) {
        if (!selectedDates.length) return;
        const s = selectedDates[0];
        const e = selectedDates.length > 1 ? selectedDates[1] : selectedDates[0];
        document.getElementById('startDate').value   = fmtDate(s);
        document.getElementById('endDate').value     = fmtDate(e);
        document.getElementById('rangeSelect').value = ''; // manual pick = Custom
        document.getElementById('filterForm').submit();
      }
    });

    // Returns the Monday of the week containing the given date.
    function startOfWeek(d) {
      const x = new Date(d);
      const day = (x.getDay() + 6) % 7; // Monday = 0
      x.setDate(x.getDate() - day);
      x.setHours(0, 0, 0, 0);
      return x;
    }

    // Fills start/end date inputs for a preset range key and submits the filter form.
    function applyQuickRange(val) {
      if (!val) return; // "Custom" — let the user pick manually
      const today = new Date();
      let start, end;
      switch (val) {
        case 'this_week':
          start = startOfWeek(today); end = today; break;
        case 'last_week':
          start = startOfWeek(today); start.setDate(start.getDate() - 7);
          end = new Date(start); end.setDate(end.getDate() + 6); break;
        case 'this_month':
          start = new Date(today.getFullYear(), today.getMonth(), 1); end = today; break;
        case 'last_month':
          start = new Date(today.getFullYear(), today.getMonth() - 1, 1);
          end   = new Date(today.getFullYear(), today.getMonth(), 0); break;
        default: return;
      }
      document.getElementById('startDate').value = fmtDate(start);
      document.getElementById('endDate').value   = fmtDate(end);
      document.getElementById('filterForm').submit();
    }
  </script>
</body>

</html>
