<?php
include 'config.php';
require_once '../php/auth.php';
requireLogin();

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

$farmFilter   = (isset($_GET['farm']) && is_numeric($_GET['farm']) && $_GET['farm'] >= 1 && $_GET['farm'] <= 8)
  ? 'Block ' . (int)$_GET['farm'] : null;
$selectedFarm   = isset($_GET['farm'])   ? (int)$_GET['farm']    : '';

$knownGrades   = ['25BCP','30BCP','33BCP','30TR','IF36TR','IF38TR'];
$gradeFilter   = (isset($_GET['grade']) && in_array($_GET['grade'], $knownGrades))
  ? $_GET['grade'] : null;
$selectedGrade = isset($_GET['grade']) ? $_GET['grade'] : '';

$sizeFilter    = (isset($_GET['size'])   && $_GET['size']   !== '') ? trim($_GET['size'])   : null;
$selectedSize  = isset($_GET['size'])   ? trim($_GET['size'])       : '';

$fingerFilter  = (isset($_GET['finger']) && $_GET['finger'] !== '') ? trim($_GET['finger']) : null;
$selectedFinger = isset($_GET['finger']) ? trim($_GET['finger'])    : '';

// Fetch distinct dates for calendar
$distinctDatesStmt = $conn->prepare("SELECT DISTINCT DATE(`timestamp`) AS valid_date FROM `Finger_classes` ORDER BY valid_date DESC");
$distinctDatesStmt->execute();
$validDates = [];
$dr = $distinctDatesStmt->get_result();
while ($r = $dr->fetch_assoc()) $validDates[] = $r['valid_date'];
$distinctDatesStmt->close();

// Fetch distinct sizes
$sizesStmt = $conn->prepare("SELECT DISTINCT `size` FROM `Finger_classes` WHERE `size` IS NOT NULL AND `size` != '' ORDER BY `size`");
$sizesStmt->execute();
$distinctSizes = [];
$sr = $sizesStmt->get_result();
while ($r = $sr->fetch_assoc()) $distinctSizes[] = $r['size'];
$sizesStmt->close();

// Fetch distinct finger counts
$fingerStmt = $conn->prepare("SELECT DISTINCT `classes_name` FROM `Finger_classes` WHERE `classes_name` IS NOT NULL AND `classes_name` != '' ORDER BY `classes_name`");
$fingerStmt->execute();
$distinctFingers = [];
$fr = $fingerStmt->get_result();
while ($r = $fr->fetch_assoc()) $distinctFingers[] = $r['classes_name'];
$fingerStmt->close();

// Main query
$baseSQL = "SELECT `id`, `classes_name`, `size`, `Farm`, `Classes`, `weight`, `conf`, DATE(`timestamp`) AS `timestamp`
            FROM `Finger_classes`";
$whereClauses = []; $params = []; $types = '';
if ($startDate)    { $whereClauses[] = "DATE(`timestamp`) >= ?"; $params[] = $startDate;    $types .= 's'; }
if ($endDate)      { $whereClauses[] = "DATE(`timestamp`) <= ?"; $params[] = $endDate;      $types .= 's'; }
if ($farmFilter)   { $whereClauses[] = "`Farm` = ?";            $params[] = $farmFilter;   $types .= 's'; }
if ($gradeFilter)  { $whereClauses[] = "`Classes` = ?";         $params[] = $gradeFilter;  $types .= 's'; }
if ($sizeFilter)   { $whereClauses[] = "`size` = ?";            $params[] = $sizeFilter;   $types .= 's'; }
if ($fingerFilter) { $whereClauses[] = "`classes_name` = ?";    $params[] = $fingerFilter; $types .= 's'; }
if (!empty($whereClauses)) $baseSQL .= " WHERE " . implode(" AND ", $whereClauses);
$baseSQL .= " ORDER BY `timestamp` DESC";

$stmt = $conn->prepare($baseSQL);
if (!empty($params)) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
if (!$result) die("Query Error: " . $conn->error);

$hasFilters = $selectedFarm || $selectedStart || $selectedEnd || $selectedGrade || $selectedSize || $selectedFinger;

// Total harvest (kg) + record count for the current filters / range
$thSql = "SELECT SUM(`weight` / 1000) AS `kg`, COUNT(*) AS `cnt` FROM `Finger_classes`";
if (!empty($whereClauses)) $thSql .= " WHERE " . implode(" AND ", $whereClauses);
$totalHarvestKg = 0.0; $totalHarvestCount = 0;
$thStmt = $conn->prepare($thSql);
if ($thStmt) {
  if (!empty($params)) $thStmt->bind_param($types, ...$params);
  $thStmt->execute();
  $thRes = $thStmt->get_result();
  if ($thRow = $thRes->fetch_assoc()) {
    $totalHarvestKg    = (float)($thRow['kg']  ?? 0);
    $totalHarvestCount = (int)  ($thRow['cnt'] ?? 0);
  }
  $thStmt->close();
}
$totalHarvestBoxes = (int)floor($totalHarvestKg / 13.5);

$gradeColors = [
  '25BCP'  => ['bg' => '#d1fae5', 'text' => '#065f46'],
  '30BCP'  => ['bg' => '#dbeafe', 'text' => '#1e40af'],
  '33BCP'  => ['bg' => '#fef3c7', 'text' => '#92400e'],
  '30TR'   => ['bg' => '#ede9fe', 'text' => '#5b21b6'],
  'IF36TR' => ['bg' => '#fce7f3', 'text' => '#9d174d'],
  'IF38TR' => ['bg' => '#ccfbf1', 'text' => '#134e4a'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Logs — Gradifier</title>
  <?php include '../php/pwa_head.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../src/styles.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" />
  <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Poppins:wght@300;400;500;600&display=swap');
    * { font-family: 'Poppins', sans-serif; }
    .font-brand { font-family: 'Montserrat', sans-serif; }
    select { appearance: none; -webkit-appearance: none; }
    tbody tr:hover { background: #f0fdf4 !important; }
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
          <h1 class="text-2xl font-bold text-gray-800 font-brand">Operation Logs</h1>
          <p class="text-sm text-gray-500 mt-0.5">Individual batch records from the sorting machine</p>
        </div>
        <button onclick="exportToPDF()"
          class="flex items-center gap-2 text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all"
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

          <!-- Block -->
          <div class="flex flex-col gap-1 min-w-[130px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Block</label>
            <div class="relative">
              <select name="farm" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All</option>
                <?php for ($i = 1; $i <= 8; $i++): ?>
                  <option value="<?= $i ?>" <?= $selectedFarm == $i ? 'selected' : '' ?>>Block <?= $i ?></option>
                <?php endfor; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Grade -->
          <div class="flex flex-col gap-1 min-w-[130px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Grade</label>
            <div class="relative">
              <select name="grade" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All</option>
                <?php foreach ($knownGrades as $g): ?>
                  <option value="<?= $g ?>" <?= $selectedGrade === $g ? 'selected' : '' ?>><?= $g ?></option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Size -->
          <div class="flex flex-col gap-1 min-w-[120px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Size</label>
            <div class="relative">
              <select name="size" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All</option>
                <?php foreach ($distinctSizes as $s): ?>
                  <option value="<?= htmlspecialchars($s) ?>" <?= $selectedSize === $s ? 'selected' : '' ?>><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Finger Count -->
          <div class="flex flex-col gap-1 min-w-[140px]">
            <label class="text-[11px] text-gray-400 font-medium uppercase tracking-wider">Finger Count</label>
            <div class="relative">
              <select name="finger" onchange="this.form.submit()"
                class="w-full bg-gray-50 border border-gray-200 text-gray-700 text-sm rounded-xl px-3 py-2 pr-8 focus:outline-none focus:border-emerald-400 focus:ring-1 focus:ring-emerald-100 cursor-pointer">
                <option value="">All</option>
                <?php foreach ($distinctFingers as $f): ?>
                  <option value="<?= htmlspecialchars($f) ?>" <?= $selectedFinger === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                <?php endforeach; ?>
              </select>
              <i class="fa-solid fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
            </div>
          </div>

          <!-- Quick Range Preset -->
          <div class="flex flex-col gap-1 min-w-[140px]">
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

          <!-- Date Range (two dates) -->
          <div class="flex flex-col gap-1 min-w-[200px]">
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
                  <i class="fa-solid fa-tractor text-xs"></i> Block <?= $selectedFarm ?>
                </span>
              <?php endif; ?>
              <?php if ($selectedGrade): ?>
                <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-tag text-xs"></i> <?= htmlspecialchars($selectedGrade) ?>
                </span>
              <?php endif; ?>
              <?php if ($selectedSize): ?>
                <span class="text-xs bg-purple-50 text-purple-700 border border-purple-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-ruler text-xs"></i> <?= htmlspecialchars($selectedSize) ?>
                </span>
              <?php endif; ?>
              <?php if ($selectedFinger): ?>
                <span class="text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2.5 py-1 rounded-full flex items-center gap-1">
                  <i class="fa-solid fa-hand text-xs"></i> <?= htmlspecialchars($selectedFinger) ?>
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
              <a href="logs.php" class="text-xs text-gray-400 hover:text-red-500 flex items-center gap-1 no-underline transition-colors">
                <i class="fa-solid fa-xmark"></i> Clear
              </a>
            </div>
          <?php endif; ?>

        </form>
      </div>

      <!-- Total Harvest Summary -->
      <div class="bg-white rounded-2xl border border-emerald-100 shadow-sm p-4 mb-6 flex flex-wrap items-center justify-between gap-4"
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

      <!-- Table Card -->
      <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
          <table class="min-w-full" id="logTable">
            <thead>
              <tr style="background:#f0fdf4; border-bottom:2px solid #d1fae5;">
                <?php foreach (['No.','Farm','Grade','Finger Count','Size','Weight','Confidence','Date'] as $h): ?>
                  <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider whitespace-nowrap" style="color:#065f46;">
                    <?= $h ?>
                  </th>
                <?php endforeach; ?>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
              <?php
              $no = 1;
              $hasRows = false;
              $totalWeight = 0.0;
              if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                  $hasRows = true;
                  if (is_numeric($row['weight'])) $totalWeight += (float)$row['weight'];
                  $isEven = $no % 2 === 0;
                  $grade = htmlspecialchars($row['Classes']);
                  $color = $gradeColors[$row['Classes']] ?? ['bg' => '#f3f4f6', 'text' => '#374151'];
                  $conf = is_numeric($row['conf']) ? round((float)$row['conf'] * 100, 1) : $row['conf'];
                  $confColor = $conf >= 80 ? '#059669' : ($conf >= 60 ? '#d97706' : '#dc2626');
                  $weightG = is_numeric($row['weight']) ? number_format((float)$row['weight'], 1) : $row['weight'];
              ?>
                <tr class="transition-colors" style="<?= $isEven ? 'background:#fafafa;' : '' ?>">
                  <td class="px-4 py-3 text-xs text-gray-400 font-medium"><?= $no++ ?></td>
                  <td class="px-4 py-3 text-sm font-semibold text-gray-700 whitespace-nowrap">
                    <?= htmlspecialchars($row['Farm']) ?>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full whitespace-nowrap"
                      style="background:<?= $color['bg'] ?>;color:<?= $color['text'] ?>;">
                      <?= $grade ?>
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['classes_name']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($row['size']) ?></td>
                  <td class="px-4 py-3 text-sm text-gray-700">
                    <?= $weightG ?> <span class="text-gray-400 text-xs">g</span>
                  </td>
                  <td class="px-4 py-3">
                    <span class="text-xs font-semibold px-2 py-0.5 rounded-md"
                      style="background:<?= $confColor ?>22;color:<?= $confColor ?>;">
                      <?= $conf ?>%
                    </span>
                  </td>
                  <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">
                    <?= htmlspecialchars($row['timestamp']) ?>
                  </td>
                </tr>
              <?php endwhile; else: $hasRows = false; endif; ?>

              <?php if (!$hasRows): ?>
                <tr>
                  <td colspan="8" class="px-4 py-16 text-center">
                    <i class="fa-solid fa-inbox text-gray-300 text-3xl mb-3 block"></i>
                    <p class="text-gray-400 text-sm">No records found<?= $hasFilters ? ' for the selected filters' : '' ?>.</p>
                    <?php if ($hasFilters): ?>
                      <a href="logs.php" class="text-emerald-600 text-xs mt-1 inline-block hover:underline">Clear filters</a>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
            <?php if ($hasRows): ?>
            <tfoot>
              <tr style="background:#f0fdf4; border-top:2px solid #d1fae5;">
                <td colspan="5" class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-right" style="color:#065f46;">
                  Total Weight
                </td>
                <td class="px-4 py-3 text-sm font-bold" style="color:#065f46;">
                  <?= number_format($totalWeight, 1) ?> <span class="text-xs font-normal" style="color:#6b7280;">g</span>
                  <span class="ml-2 text-xs font-normal" style="color:#6b7280;">(<?= number_format($totalWeight / 1000, 2) ?> kg)</span>
                </td>
                <td colspan="2"></td>
              </tr>
            </tfoot>
            <?php endif; ?>
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

    document.addEventListener("DOMContentLoaded", function () {
      const rows = document.querySelectorAll("tbody tr");
      const rowsPerPage = 12;
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
        pageInfo.textContent = `Page ${page} of ${totalPages}`;
        prevBtn.disabled = page === 1;
        nextBtn.disabled = page === totalPages;
      }

      prevBtn.addEventListener("click", () => { if (currentPage > 1) displayRows(--currentPage); });
      nextBtn.addEventListener("click", () => { if (currentPage < totalPages) displayRows(++currentPage); });
      displayRows(1);
    });

    // Exports the log table (including footer total) to a PDF via jsPDF.
    function exportToPDF() {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      const now = new Date();
      const dateStr = now.toISOString().slice(0, 10);
      const pageWidth = doc.internal.pageSize.getWidth();

      doc.setFontSize(10); doc.setFont("helvetica", "normal");
      const tsText = now.toLocaleString();
      doc.text(tsText, pageWidth - doc.getTextWidth(tsText) - 14, 10);

      const title = "Block Operation Logs";
      doc.setFontSize(16); doc.setFont("helvetica", "bold");
      doc.text(title, (pageWidth - doc.getTextWidth(title)) / 2, 16);

      const rows = [];
      document.querySelectorAll("tbody tr").forEach(tr => {
        const rowData = [];
        tr.querySelectorAll("td").forEach(td => rowData.push(td.innerText.trim()));
        if (rowData.length > 1) rows.push(rowData);
      });

      const footRow = [];
      const tfootTds = document.querySelectorAll("tfoot tr td");
      if (tfootTds.length) {
        const weightText = tfootTds[1] ? tfootTds[1].innerText.trim() : '';
        footRow.push(["", "", "", "", "Total Weight", weightText, "", ""]);
      }

      doc.autoTable({
        head: [["#", "Block", "Grade", "Finger Count", "Size", "Weight", "Confidence", "Date"]],
        body: rows,
        foot: footRow,
        startY: 24,
        theme: 'grid',
        headStyles: { fillColor: [6, 95, 70], textColor: 255, fontStyle: 'bold', halign: 'center' },
        footStyles: { fillColor: [240, 253, 244], textColor: [6, 95, 70], fontStyle: 'bold', halign: 'center' },
        styles: { halign: 'center', fontSize: 9 },
        showFoot: 'lastPage',
      });

      doc.save(`Logs_${dateStr}.pdf`);
    }
  </script>

  <script>
    const availableDates = <?= json_encode($validDates) ?>;
    const selStart       = <?= json_encode($selectedStart ?: null) ?>;
    const selEnd         = <?= json_encode($selectedEnd ?: null) ?>;

    // Formats a Date object as a YYYY-MM-DD string.
    function fmtDate(d) {
      return d.getFullYear() + '-'
        + String(d.getMonth() + 1).padStart(2, '0') + '-'
        + String(d.getDate()).padStart(2, '0');
    }

    // ---- Date range picker ----
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
