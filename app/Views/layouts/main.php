<?php

declare(strict_types=1);

use App\Core\Csrf;
use App\Services\AuthService;

$csrfToken = Csrf::token();
$auth = new AuthService();
$authUser = $auth->user();
$dashboardPath = $authUser !== null ? $auth->dashboardPathForRole($authUser['role']) : '/login';
$content = $content ?? '';
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
  <link rel="stylesheet" href="<?= e(asset('assets/css/layout-shell.css')) ?>">
</head>

<body class="bg-emerald-50 text-slate-800 min-h-screen">
  <?php require base_path('app/Views/layouts/header.php'); ?>

  <main class="template-main">
    <?= $content ?>
  </main>

  <?php require base_path('app/Views/layouts/footer.php'); ?>

  <script>window.NOURISH_CSRF = <?= json_encode($csrfToken, JSON_UNESCAPED_UNICODE) ?>;</script>
  <script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>

</html>