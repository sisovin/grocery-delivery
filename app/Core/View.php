<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
  /** @param array<string, mixed> $data */
  public static function render(string $view, array $data = [], string $layout = 'layouts/main'): void
  {
    $viewFile = base_path('app/Views/' . $view . '.php');
    $layoutFile = base_path('app/Views/' . $layout . '.php');

    if (!is_file($viewFile) || !is_file($layoutFile)) {
      Response::abort(500, 'View not found');
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $viewFile;
    $content = (string) ob_get_clean();

    require $layoutFile;
  }
}
