<?php

declare(strict_types=1);

/** @var array<int, App\Models\Product> $products */
/** @var array<int, string> $provinces */

$imageMap = [
  1 => 'assets/images/01-product-tomato.jpg',
  2 => 'assets/images/02-product-avocado.jpg',
  3 => 'assets/images/03-product-greens.jpg',
  4 => 'assets/images/04-product-berries.jpg',
  5 => 'assets/images/01-popular-product.png',
  6 => 'assets/images/01-hero.png',
  7 => 'assets/images/01-mobile-promotion.png',
  8 => 'assets/images/01-hero-fresh.jpg',
  9 => 'assets/images/03-product-greens.jpg',
  10 => 'assets/images/02-product-avocado.jpg',
];
?>
<section class="mt-8 bg-white rounded-2xl p-4 border border-emerald-100">
  <p class="text-sm text-slate-600">Coverage: <?= e(implode(' • ', $provinces)) ?></p>
</section>

<section class="mt-8">
  <div class="flex items-end justify-between mb-4">
    <h2 class="text-2xl font-bold text-emerald-800">Sample Products</h2>
    <span class="text-sm text-slate-500">Framework-aligned outputs</span>
  </div>

  <div id="products" class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($products as $product): ?>
      <?php $imagePath = $imageMap[$product->id] ?? 'assets/images/01-popular-product.png'; ?>
      <?php require base_path('app/Views/components/home/product-card.php'); ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="mt-10 bg-white rounded-2xl border border-emerald-100 p-6">
  <h2 class="text-xl font-bold text-emerald-800 mb-4">Prompt Generator API Demo</h2>
  <form id="promptForm" class="grid md:grid-cols-3 gap-3">
    <input class="border border-slate-300 rounded-lg px-3 py-2" type="text" name="product" placeholder="ឈ្មោះផលិតផល"
      required>
    <input class="border border-slate-300 rounded-lg px-3 py-2" type="text" name="origin" placeholder="ប្រភព" required>
    <input class="border border-slate-300 rounded-lg px-3 py-2" type="text" name="deal" placeholder="Deal (optional)">
    <input type="hidden" name="_token" value="<?= e(App\Core\Csrf::token()) ?>">
    <button class="md:col-span-3 bg-slate-900 text-white rounded-lg px-4 py-2 hover:bg-slate-800" type="submit">Generate
      Khmer Content</button>
  </form>
  <pre id="promptOutput" class="mt-4 bg-slate-900 text-emerald-200 rounded-xl p-4 text-sm overflow-x-auto"></pre>
</section>