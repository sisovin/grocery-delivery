<?php

declare(strict_types=1);
?>
<section class="bg-white rounded-3xl p-6 border border-emerald-100 mb-6 grid lg:grid-cols-2 gap-5 items-center">
  <div>
    <h1 class="text-2xl font-bold text-emerald-800 mb-2">Supplier Content Hub</h1>
    <p class="text-slate-600">Manage listings and generate web, social, email, and push-ready Khmer copy aligned with
      Nourish voice.</p>
  </div>
  <img class="w-full rounded-2xl border border-emerald-100"
    src="<?= e(asset('assets/images/01-popular-product.png')) ?>" alt="Supplier produce curation preview">
</section>

<section class="grid md:grid-cols-2 gap-4">
  <?php $title = 'Quality Pillar';
  $body = 'Attach organic proof, harvest time, and farm origin in every listing.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
  <?php $title = 'Trust Pillar';
  $body = 'Highlight local farms and secure checkout trust point.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
</section>

<section class="mt-4 grid md:grid-cols-2 gap-4">
  <?php $title = 'Channel Outputs';
  $body = 'Produce reusable copy blocks for web, app, social, push, and SMS channels.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
  <?php $title = 'Supplier Role Guard';
  $body = 'Only supplier accounts can open this page through RBAC route checks.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
</section>