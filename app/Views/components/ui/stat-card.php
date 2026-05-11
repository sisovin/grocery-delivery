<?php

declare(strict_types=1);

/** @var string $label */
/** @var string $value */
/** @var string $tone */
$tone = $tone ?? 'emerald';

$palette = match ($tone) {
  'amber' => 'bg-amber-50 border-amber-100 text-amber-800',
  'blue' => 'bg-blue-50 border-blue-100 text-blue-800',
  default => 'bg-emerald-50 border-emerald-100 text-emerald-800',
};
?>
<div class="rounded-2xl border p-5 <?= e($palette) ?>">
  <p class="text-sm opacity-70"><?= e($label) ?></p>
  <h2 class="text-3xl font-bold mt-1"><?= e($value) ?></h2>
</div>