<?php

declare(strict_types=1);

/** @var array<int, App\Models\Product> $products */
/** @var array<int, string> $provinces */
?>
<section
  class="rounded-3xl bg-gradient-to-br from-emerald-700 via-emerald-600 to-lime-500 text-white p-8 lg:p-12 relative overflow-hidden">
  <div class="absolute -top-24 -right-24 h-72 w-72 rounded-full bg-white/20 blur-2xl"></div>
  <h1 class="text-3xl lg:text-5xl font-bold mb-4">Earth's finest ដឹកជញ្ជូនដល់ផ្ទះអ្នក</h1>
  <p class="text-emerald-50 max-w-3xl leading-8">វេទិកាដឹកជញ្ជូនម្ហូបស្រស់ និងសរីរាង្គដែលមានគុណភាពខ្ពស់។ Nourish your
    home ជាមួយផលិតផលពី Local farms និងសេវាដឹកជញ្ជូនថ្ងៃតែមួយ។</p>
  <div class="mt-6 flex flex-wrap gap-3 text-sm">
    <span class="px-4 py-2 rounded-full bg-white/20">Free Delivery $20+</span>
    <span class="px-4 py-2 rounded-full bg-white/20">Same-Day Delivery</span>
    <span class="px-4 py-2 rounded-full bg-white/20">Secure Payment 100%</span>
  </div>
</section>

<section class="mt-8 bg-white rounded-2xl p-4 border border-emerald-100">
  <p class="text-sm text-slate-600">Coverage: <?= e(implode(' • ', $provinces)) ?></p>
</section>

<section class="mt-8">
  <div class="flex items-end justify-between mb-4">
    <h2 class="text-2xl font-bold text-emerald-800">Sample Products</h2>
    <span class="text-sm text-slate-500">Framework-aligned outputs</span>
  </div>

  <div class="grid md:grid-cols-2 xl:grid-cols-3 gap-5">
    <?php foreach ($products as $product): ?>
      <article class="bg-white rounded-2xl border border-emerald-100 p-5 shadow-sm hover:shadow-md transition">
        <div class="flex items-center justify-between gap-2 mb-2">
          <h3 class="text-lg font-semibold text-emerald-800"><?= e($product->name) ?></h3>
          <span
            class="text-xs px-2 py-1 rounded-full bg-amber-100 text-amber-700"><?= e($product->deal ?? 'Seasonal') ?></span>
        </div>
        <p class="text-sm text-slate-500 mb-3">Origin: <?= e($product->origin) ?>, <?= e($product->province) ?></p>
        <p class="text-slate-700 mb-4 leading-7"><?= e($product->description) ?></p>
        <div class="flex items-center justify-between">
          <strong class="text-emerald-700">$<?= number_format($product->price, 2) ?></strong>
          <a href="/products/<?= $product->id ?>"
            class="px-4 py-2 rounded-lg bg-emerald-700 text-white text-sm hover:bg-emerald-600">View</a>
        </div>
      </article>
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