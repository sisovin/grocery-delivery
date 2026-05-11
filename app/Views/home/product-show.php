<?php

declare(strict_types=1);

/** @var App\Models\Product $product */
?>
<article class="max-w-3xl mx-auto bg-white rounded-3xl border border-emerald-100 p-8">
  <div class="mb-4 text-sm text-slate-500"><a class="text-emerald-700 hover:underline" href="/">← Back to products</a>
  </div>
  <h1 class="text-3xl font-bold text-emerald-800 mb-2"><?= e($product->name) ?></h1>
  <p class="text-slate-500 mb-4">Origin: <?= e($product->origin) ?> | Province: <?= e($product->province) ?></p>
  <p class="leading-8 text-slate-700 mb-6"><?= e($product->description) ?></p>
  <div class="grid sm:grid-cols-3 gap-3 text-sm">
    <div class="rounded-xl bg-emerald-50 p-3">Farm-fresh</div>
    <div class="rounded-xl bg-emerald-50 p-3">Same-day delivery</div>
    <div class="rounded-xl bg-emerald-50 p-3">Secure payment</div>
  </div>
  <div class="mt-6 flex items-center justify-between">
    <strong class="text-2xl text-emerald-700">$<?= number_format($product->price, 2) ?></strong>
    <button class="rounded-xl px-5 py-3 bg-emerald-700 text-white hover:bg-emerald-600">Nourish your home</button>
  </div>
</article>