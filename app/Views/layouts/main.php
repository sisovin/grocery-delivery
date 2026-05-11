<?php

declare(strict_types=1);

use App\Core\Csrf;

$csrfToken = Csrf::token();
?>
<!doctype html>
<html lang="km">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title ?? 'Nourish') ?></title>
  <meta name="csrf-token" content="<?= e($csrfToken) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Khmer:wght@400;500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>

<body class="bg-emerald-50 text-slate-800 min-h-screen">
  <header class="bg-gradient-to-r from-emerald-700 to-green-500 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
      <a href="/" class="font-bold text-2xl tracking-tight">Nourish</a>
      <nav class="flex flex-wrap gap-2 text-sm">
        <a class="px-3 py-2 rounded-full bg-white/15 hover:bg-white/25" href="/">Home</a>
        <a class="px-3 py-2 rounded-full bg-white/15 hover:bg-white/25" href="/admin">Admin</a>
        <a class="px-3 py-2 rounded-full bg-white/15 hover:bg-white/25" href="/customer">Customer</a>
        <a class="px-3 py-2 rounded-full bg-white/15 hover:bg-white/25" href="/supplier">Supplier</a>
      </nav>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-4 py-8">
    <?= $content ?>
  </main>

  <footer class="bg-slate-900 text-slate-300 mt-10">
    <div class="max-w-7xl mx-auto px-4 py-6 text-sm">
      Nourish your home | Earth's finest | Farm-fresh delivery across 24 provinces.
    </div>
  </footer>

  <script>window.NOURISH_CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>

</html>