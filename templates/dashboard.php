<?php
require_once 'auth_check.php';
require_once '../php/auth.php';
requireLogin();
require_once 'config.php';

$grades = ['25BCP', '30BCP', '33BCP', '30TR', 'IF36TR', 'IF38TR'];
$boxesPerGrade = array_fill_keys($grades, 0);
$bResult = $conn->query(
  "SELECT `Classes`, FLOOR(SUM(`weight` / 1000) / 13.5) AS `boxes`
   FROM `Finger_classes` WHERE `weight` >= 0 GROUP BY `Classes`"
);
if ($bResult) {
  while ($r = $bResult->fetch_assoc()) {
    if (isset($boxesPerGrade[$r['Classes']])) $boxesPerGrade[$r['Classes']] = (int)$r['boxes'];
  }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard — Gradifier</title>
  <?php include '../php/pwa_head.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="../src/styles.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@700;900&family=Poppins:wght@300;400;500;600&display=swap');
    * { font-family: 'Poppins', sans-serif; }
    .font-brand { font-family: 'Montserrat', sans-serif; }
  </style>
</head>

<body class="bg-gray-50 min-h-screen flex flex-col">

  <div id="header-placeholder" class="sticky top-0 z-30"></div>

  <div class="flex flex-1">
    <div id="sidebar-placeholder" class="flex-shrink-0"></div>

    <main class="flex-1 p-6 overflow-x-hidden min-w-0">

      <!-- Page Title -->
      <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 font-brand">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">Banana grading overview — all blocks</p>
      </div>

      <!-- Stat Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 flex-shrink-0">
            <i class="fa-solid fa-weight-hanging"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-gray-400 font-medium uppercase tracking-wide">Total Weight</p>
            <p id="val-total" class="text-2xl font-bold text-gray-800 leading-tight">—</p>
            <p class="text-[11px] text-gray-400">kg across all grades</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-yellow-50 flex items-center justify-center text-yellow-500 flex-shrink-0">
            <i class="fa-solid fa-trophy"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-gray-400 font-medium uppercase tracking-wide">Top Grade</p>
            <p id="val-top" class="text-2xl font-bold text-gray-800 leading-tight">—</p>
            <p id="val-top-sub" class="text-[11px] text-gray-400">by total weight</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-blue-500 flex-shrink-0">
            <i class="fa-solid fa-boxes-stacked"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-gray-400 font-medium uppercase tracking-wide">Total Batches</p>
            <p id="val-batches" class="text-2xl font-bold text-gray-800 leading-tight">—</p>
            <p class="text-[11px] text-gray-400">sorted records in DB</p>
          </div>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
          <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center text-purple-500 flex-shrink-0">
            <i class="fa-solid fa-clock-rotate-left"></i>
          </div>
          <div class="min-w-0">
            <p class="text-[11px] text-gray-400 font-medium uppercase tracking-wide">Last Activity</p>
            <p id="val-latest" class="text-lg font-bold text-gray-800 leading-tight truncate">—</p>
            <p class="text-[11px] text-gray-400">most recent batch</p>
          </div>
        </div>

      </div>

      <!-- Boxes per Grade -->
      <div class="mb-6">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Total Boxes per Grade <span class="text-gray-300 font-normal normal-case">@ 13.5 kg/box</span></h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
          <?php
          $gradeColors = [
            '25BCP'  => ['border'=>'border-blue-200',   'text'=>'text-blue-700',   'sub'=>'text-blue-400'],
            '30BCP'  => ['border'=>'border-orange-200', 'text'=>'text-orange-700', 'sub'=>'text-orange-400'],
            '33BCP'  => ['border'=>'border-emerald-200','text'=>'text-emerald-700','sub'=>'text-emerald-400'],
            '30TR'   => ['border'=>'border-purple-200', 'text'=>'text-purple-700', 'sub'=>'text-purple-400'],
            'IF36TR' => ['border'=>'border-red-200',    'text'=>'text-red-700',    'sub'=>'text-red-400'],
            'IF38TR' => ['border'=>'border-yellow-200', 'text'=>'text-yellow-700', 'sub'=>'text-yellow-400'],
          ];
          foreach ($grades as $g):
            $c = $gradeColors[$g];
          ?>
          <div class="bg-white rounded-2xl border <?= $c['border'] ?> shadow-sm p-4 flex flex-col gap-1">
            <span class="text-[11px] font-semibold uppercase tracking-wider <?= $c['text'] ?>"><?= $g ?></span>
            <div class="flex items-end gap-1.5 mt-1">
              <span class="text-2xl font-bold text-gray-800"><?= number_format($boxesPerGrade[$g]) ?></span>
              <span class="text-xs <?= $c['sub'] ?> mb-0.5">boxes</span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Chart Card -->
      <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-5">
          <div>
            <h2 class="text-base font-semibold text-gray-800">Weight Distribution by Grade</h2>
            <p class="text-xs text-gray-400 mt-0.5">Total sorted weight (kg) per banana grade class</p>
          </div>
          <span class="text-xs bg-emerald-50 text-emerald-600 font-medium px-3 py-1 rounded-full border border-emerald-100 flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse inline-block"></span>
            Live
          </span>
        </div>
        <div class="flex justify-center items-center" style="height: 380px;">
          <canvas id="classPieChart"></canvas>
        </div>
      </div>

    </main>
  </div>

  <script>
    $(function () {
      $("#header-placeholder").load("header.php");
      $("#sidebar-placeholder").load("sidebar.html");
    });
  </script>
  <script src="../javascript/chart.js?v=<?= filemtime(dirname(__FILE__) . '/../javascript/chart.js') ?>"></script>
  <script src="../javascript/dropdown.js"></script>

</body>

</html>
