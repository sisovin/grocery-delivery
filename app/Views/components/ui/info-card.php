<?php

declare(strict_types=1);

/** @var string $title */
/** @var string $body */
?>
<article class="bg-white border border-emerald-100 rounded-2xl p-5">
  <h3 class="font-semibold text-emerald-800 mb-2"><?= e($title) ?></h3>
  <p class="text-sm text-slate-600"><?= e($body) ?></p>
</article>