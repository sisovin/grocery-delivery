<?php

declare(strict_types=1);

namespace App\Core;

final class Csrf
{
  public static function token(): string
  {
    $token = Session::get('_csrf_token');
    if (is_string($token) && $token !== '') {
      return $token;
    }

    $token = bin2hex(random_bytes(32));
    Session::put('_csrf_token', $token);
    return $token;
  }

  public static function validate(?string $token): bool
  {
    if ($token === null || $token === '') {
      return false;
    }

    $stored = Session::get('_csrf_token');
    return is_string($stored) && hash_equals($stored, $token);
  }
}
