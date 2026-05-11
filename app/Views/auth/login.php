<?php

declare(strict_types=1);
?>
<section class="max-w-md mx-auto bg-white rounded-2xl border border-emerald-100 shadow-sm p-6 mt-6">
  <h1 class="text-2xl font-bold text-emerald-800 mb-2">Sign In</h1>
  <p class="text-slate-600 mb-5">Access your admin, customer, or supplier workspace.</p>

  <?php if (!empty($status)): ?>
    <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-3 py-2 text-sm">
      <?= e((string) $status) ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-3 py-2 text-sm"><?= e((string) $error) ?>
    </div>
  <?php endif; ?>

  <form action="/login" method="post" class="space-y-4">
    <input type="hidden" name="_token" value="<?= e(App\Core\Csrf::token()) ?>">

    <label class="block">
      <span class="text-sm font-medium text-slate-700">Email</span>
      <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" type="email" name="email" required>
    </label>

    <label class="block">
      <span class="text-sm font-medium text-slate-700">Password</span>
      <input class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2" type="password" name="password" required>
    </label>

    <button class="w-full rounded-lg bg-emerald-700 text-white py-2.5 font-semibold hover:bg-emerald-600"
      type="submit">Sign In</button>
  </form>

  <p class="mt-4 text-sm text-slate-600">No account yet? <a class="text-emerald-700 hover:underline"
      href="/register">Create one</a></p>
  <p class="mt-2 text-xs text-slate-500">Tip: run php bin/console seed:users to create demo admin/customer/supplier
    users.</p>
</section>