<?php
require_once 'php/auth.php';
if (isLoggedIn()) {
  header('Location: /Grade/templates/dashboard.php');
  exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <title>Gradifier — Banana Sorting & Grading</title>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <?php include 'php/pwa_head.php'; ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="src/styles.css" />
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
    integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800;900&family=Poppins:wght@300;400;500&display=swap');

    * { font-family: 'Poppins', sans-serif; }
    .font-brand { font-family: 'Montserrat', sans-serif; }

    .hero-bg {
      background: linear-gradient(135deg, #064e3b 0%, #065f46 40%, #047857 100%);
    }
    .card-glass {
      background: rgba(255, 255, 255, 0.07);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(255, 255, 255, 0.15);
    }
    .image-card {
      background: linear-gradient(145deg, rgba(255,255,255,0.12), rgba(255,255,255,0.04));
      border: 1px solid rgba(255,255,255,0.2);
    }
    .btn-login {
      background: linear-gradient(135deg, #10b981, #059669);
      transition: all 0.3s ease;
      box-shadow: 0 4px 20px rgba(16, 185, 129, 0.35);
    }
    .btn-login:hover {
      background: linear-gradient(135deg, #34d399, #10b981);
      box-shadow: 0 6px 28px rgba(16, 185, 129, 0.55);
      transform: translateY(-2px);
    }
    .badge {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid rgba(16, 185, 129, 0.4);
    }
    .team-card {
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.1);
      transition: background 0.2s;
    }
    .team-card:hover {
      background: rgba(255,255,255,0.11);
    }
    .grade-tag {
      background: rgba(16, 185, 129, 0.15);
      border: 1px solid rgba(16, 185, 129, 0.3);
      font-size: 0.6rem;
      letter-spacing: 0.08em;
    }
  </style>
</head>

<body class="hero-bg min-h-screen flex flex-col overflow-x-hidden">

  <!-- Main Hero -->
  <div class="flex-1 flex items-start lg:items-center justify-center px-4 sm:px-6 py-10 lg:py-8">
    <div class="w-full max-w-6xl grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12 items-center">

      <!-- Left: Machine Image -->
      <div class="flex justify-center lg:justify-end order-2 lg:order-1">
        <div class="image-card rounded-3xl p-3 w-full max-w-xs sm:max-w-sm md:max-w-md shadow-2xl">
          <img
            src="/grade/img/model.png"
            alt="Banana Sorting Machine"
            class="rounded-2xl w-full h-auto object-cover"
          />
          <div class="mt-3 flex flex-wrap gap-1.5 px-1 pb-1">
            <?php
            $grades = ['25BCP','30BCP','33BCP','30TR','IF36TR','IF38TR'];
            foreach ($grades as $g): ?>
              <span class="grade-tag text-emerald-300 font-semibold px-2 py-0.5 rounded-full"><?= $g ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Right: Brand & CTA -->
      <div class="flex flex-col gap-5 order-1 lg:order-2 text-white">

        <!-- Badge -->
        <div class="flex justify-center lg:justify-start">
          <span class="badge text-emerald-300 text-xs font-medium px-3 py-1 rounded-full flex items-center gap-1.5">
            <i class="fa-solid fa-leaf text-xs"></i>
            Philippines' First Banana Grading System
          </span>
        </div>

        <!-- Title -->
        <div class="text-center lg:text-left">
          <h1 class="font-brand text-5xl sm:text-6xl lg:text-7xl font-black text-white leading-none tracking-tight">
            GRADI<span class="text-emerald-400">FIER</span>
          </h1>
          <p class="mt-3 text-emerald-200 text-sm sm:text-base font-light leading-relaxed max-w-md mx-auto lg:mx-0">
            Automated banana sorting and classification to international export standards.
            Real-time grade analytics for farms and quality managers.
          </p>
        </div>

        <!-- Feature Pills -->
        <div class="flex flex-wrap justify-center lg:justify-start gap-2">
          <span class="flex items-center gap-1.5 text-xs text-emerald-100 bg-white/10 rounded-full px-3 py-1.5">
            <i class="fa-solid fa-chart-pie text-emerald-400 text-xs"></i> Grade Analytics
          </span>
          <span class="flex items-center gap-1.5 text-xs text-emerald-100 bg-white/10 rounded-full px-3 py-1.5">
            <i class="fa-solid fa-scale-balanced text-emerald-400 text-xs"></i> Weight Tracking
          </span>
          <span class="flex items-center gap-1.5 text-xs text-emerald-100 bg-white/10 rounded-full px-3 py-1.5">
            <i class="fa-solid fa-tractor text-emerald-400 text-xs"></i> Block Management
          </span>
          <span class="flex items-center gap-1.5 text-xs text-emerald-100 bg-white/10 rounded-full px-3 py-1.5">
            <i class="fa-solid fa-wifi text-emerald-400 text-xs"></i> Works Offline
          </span>
        </div>

        <!-- CTA -->
        <div class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-3">
          <a href="/Grade/templates/login.php"
            class="btn-login text-white font-semibold text-sm px-8 py-3 rounded-xl flex items-center gap-2 no-underline w-full sm:w-auto justify-center">
            <i class="fa-solid fa-arrow-right-to-bracket"></i>
            Sign In
          </a>
          <p class="text-emerald-300/70 text-xs">Authorized personnel only</p>
        </div>

      </div>
    </div>
  </div>

  <!-- Team Footer -->
  <div class="card-glass border-t border-white/10 px-4 sm:px-6 py-4">
    <div class="max-w-6xl mx-auto flex flex-col items-center gap-3">

      <p class="text-emerald-300/60 text-xs font-medium tracking-widest uppercase">Development Team</p>

      <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 w-full sm:w-auto">
        <?php
        $team = [
          ['name' => 'DALURO, HANNAGENE',  'img' => '/grade/img/hanna.png', 'role' => 'Developer'],
          ['name' => 'PALOMATA, PIOLO',     'img' => '/grade/img/fred.png',  'role' => 'Developer'],
          ['name' => 'VILLANUEVA, MEAVE',   'img' => '/grade/img/meave.jpg', 'role' => 'Developer'],
          ['name' => 'VITANGCOR, ALFREDO',  'img' => '/grade/img/fred.png',  'role' => 'Developer'],
        ];
        foreach ($team as $member): ?>
          <div class="team-card flex items-center gap-2 rounded-xl px-3 py-2 min-w-0">
            <img src="<?= $member['img'] ?>" alt="<?= $member['name'] ?>"
              class="w-8 h-8 rounded-full object-cover ring-2 ring-emerald-500/40 flex-shrink-0" />
            <div class="min-w-0">
              <p class="text-white text-[10px] font-semibold leading-tight truncate"><?= $member['name'] ?></p>
              <p class="text-emerald-400/70 text-[9px]"><?= $member['role'] ?></p>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>

</body>

</html>
