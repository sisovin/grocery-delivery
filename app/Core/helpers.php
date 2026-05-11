<?php

declare(strict_types=1);

if (!function_exists('base_path')) {
  function base_path(string $path = ''): string
  {
    $base = BASE_PATH;
    return $path === '' ? $base : $base . '/' . ltrim($path, '/');
  }
}

if (!function_exists('asset')) {
  function asset(string $path): string
  {
    return '/' . ltrim($path, '/');
  }
}

if (!function_exists('e')) {
  function e(string $value): string
  {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
  }
}
