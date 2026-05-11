<?php

declare(strict_types=1);
?>
<section class="bg-white rounded-3xl p-6 border border-emerald-100 mb-6 grid lg:grid-cols-2 gap-5 items-center">
  <div>
    <h1 class="text-2xl font-bold text-emerald-800 mb-2">សួស្តី! Welcome to Nourish</h1>
    <p class="text-slate-600">Browse Farm-fresh products, get same-day delivery, and unlock free delivery on orders
      above
      $20.</p>
  </div>
  <img class="w-full rounded-2xl border border-emerald-100"
    src="<?= e(asset('assets/images/01-mobile-promotion.png')) ?>" alt="Nourish customer mobile ordering experience">
</section>

<section class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
  <?php $title = 'Quick Reorder';
  $body = 'Order last week favorites in one click.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
  <?php $title = 'Delivery Tracker';
  $body = 'Track driver and ETA in real time.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
  <?php $title = 'Seasonal Picks';
  $body = 'Discover local farms and seasonal bundles.';
  require base_path('app/Views/components/ui/info-card.php'); ?>
</section>