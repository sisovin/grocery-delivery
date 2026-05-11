<?php

declare(strict_types=1);
?>
<section class="grid lg:grid-cols-4 gap-4">
  <?php $label = 'Total Products';
  $value = '128';
  $tone = 'emerald';
  require base_path('app/Views/components/ui/stat-card.php'); ?>
  <?php $label = 'Today Orders';
  $value = '64';
  $tone = 'blue';
  require base_path('app/Views/components/ui/stat-card.php'); ?>
  <?php $label = 'Same-Day Rate';
  $value = '97%';
  $tone = 'emerald';
  require base_path('app/Views/components/ui/stat-card.php'); ?>
  <?php $label = 'Secure Payments';
  $value = '100%';
  $tone = 'amber';
  require base_path('app/Views/components/ui/stat-card.php'); ?>
</section>

<section class="mt-6 bg-white rounded-2xl p-6 border border-emerald-100">
  <h3 class="text-xl font-semibold text-emerald-800 mb-2">Prompt Engineering Dashboard</h3>
  <p class="text-slate-600 leading-7">This page maps to your admin template intent: generate consistent Khmer content
    with brand voice, trust pillars, and CTA format for all product campaigns.</p>
</section>

<section class="mt-6 grid md:grid-cols-2 gap-4">
  <?php $title = 'Role-Safe Access';
  $body = 'RBAC is enforced per route. Admin users can access this dashboard only.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
  <?php $title = 'Seeder Pipeline';
  $body = 'Use php bin/console seed:templates to import product copy from all template sources.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
</section>