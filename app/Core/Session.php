<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
  public static function start(): void
  {
    if (session_status() === PHP_SESSION_ACTIVE) {
      return;
    }

    session_set_cookie_params([
      'httponly' => true,
      'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
      'samesite' => 'Lax',
      'path' => '/',
    ]);

    session_start();
  }

  public static function get(string $key, mixed $default = null): mixed
  {
    return $_SESSION[$key] ?? $default;
  }

  public static function put(string $key, mixed $value): void
  {
    $_SESSION[$key] = $value;
  }

  public static function has(string $key): bool
  {
    return array_key_exists($key, $_SESSION);
  }

  public static function forget(string $key): void
  {
    unset($_SESSION[$key]);
  }

  public static function regenerate(): void
  {
    if (session_status() === PHP_SESSION_ACTIVE) {
      session_regenerate_id(true);
    }
  }

  public static function destroy(): void
  {
    if (session_status() !== PHP_SESSION_ACTIVE) {
      return;
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();
      setcookie(
        session_name(),
        '',
        [
          'expires' => time() - 42000,
          'path' => $params['path'],
          'domain' => $params['domain'],
          'secure' => (bool) $params['secure'],
          'httponly' => (bool) $params['httponly'],
          'samesite' => $params['samesite'] ?? 'Lax',
        ]
      );
    }

    session_destroy();
  }

  public static function flash(string $key, mixed $value): void
  {
    $_SESSION['_flash'][$key] = $value;
  }

  public static function consumeFlash(string $key, mixed $default = null): mixed
  {
    if (!isset($_SESSION['_flash'][$key])) {
      return $default;
    }

    $value = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $value;
  }
}
