<?php

declare(strict_types=1);
?>
<section
  class="grid lg:grid-cols-2 gap-6 items-stretch rounded-3xl bg-gradient-to-br from-emerald-700 via-emerald-600 to-lime-500 text-white p-6 lg:p-10 relative overflow-hidden">
  <div class="absolute -top-24 -right-20 h-72 w-72 rounded-full bg-white/15 blur-2xl"></div>

  <div class="relative z-10">
    <h1 class="text-3xl lg:text-5xl font-bold mb-4">Earth's finest ដឹកជញ្ជូនដល់ផ្ទះអ្នក</h1>
    <p class="text-emerald-50 leading-8 max-w-2xl">វេទិកាដឹកជញ្ជូនម្ហូបស្រស់ និងសរីរាង្គដែលមានគុណភាពខ្ពស់។ Nourish your
      home ជាមួយផលិតផលពី Local farms និងដឹកជញ្ជូនថ្ងៃតែមួយ។</p>

    <div class="mt-6 flex flex-wrap gap-3 text-sm">
      <span class="px-4 py-2 rounded-full bg-white/20">Free Delivery $20+</span>
      <span class="px-4 py-2 rounded-full bg-white/20">Same-Day Delivery</span>
      <span class="px-4 py-2 rounded-full bg-white/20">Secure Payment 100%</span>
    </div>

    <div class="mt-6 flex flex-wrap gap-3">
      <a href="#products"
        class="px-5 py-3 rounded-xl bg-white text-emerald-700 font-semibold hover:bg-emerald-50">ស្វែងរកផលិតផល</a>
      <a href="/customer"
        class="px-5 py-3 rounded-xl bg-slate-900/20 text-white font-semibold hover:bg-slate-900/30">Customer
        Experience</a>
    </div>
  </div>

  <div class="relative z-10">
    <img class="w-full h-full min-h-[260px] object-cover rounded-2xl border border-white/30 shadow-2xl"
      src="<?= e(asset('assets/images/01-hero-fresh.jpg')) ?>" alt="Fresh produce spread for Nourish delivery">
  </div>
</section>