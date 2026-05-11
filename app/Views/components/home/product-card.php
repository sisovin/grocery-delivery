<?php

declare(strict_types=1);

/** @var App\Models\Product $product */
/** @var string $imagePath */
?>
<article class="bg-white rounded-2xl border border-emerald-100 overflow-hidden shadow-sm hover:shadow-lg transition">
  <img class="w-full h-44 object-cover" src="<?= e(asset($imagePath)) ?>" alt="<?= e($product->name) ?>">

  <div class="p-5">
    <div class="flex items-center justify-between gap-2 mb-2">
      <h3 class="text-lg font-semibold text-emerald-800"><?= e($product->name) ?></h3>
      <span
        class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700"><?= e($product->deal ?? 'Seasonal') ?></span>
    </div>

    <p class="text-sm text-slate-500 mb-3">Origin: <?= e($product->origin) ?>, <?= e($product->province) ?></p>
    <p class="text-slate-700 text-sm leading-7 line-clamp-3"><?= e($product->description) ?></p>

    <div class="mt-4 flex items-center justify-between">
      <strong class="text-emerald-700 text-lg">$<?= number_format($product->price, 2) ?></strong>
      <a href="/products/<?= $product->id ?>"
        class="px-4 py-2 rounded-lg bg-emerald-700 text-white text-sm hover:bg-emerald-600">View</a>
    </div>
  </div>
</article>