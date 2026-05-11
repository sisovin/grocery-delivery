<?php

declare(strict_types=1);

$toBool = static function (string $value, bool $default): bool {
  $normalized = strtolower(trim($value));
  if (in_array($normalized, ['1', 'true', 'on', 'yes'], true)) {
    return true;
  }

  if (in_array($normalized, ['0', 'false', 'off', 'no'], true)) {
    return false;
  }

  return $default;
};

$toInt = static function (string $value, int $default): int {
  return filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : $default;
};

return [
  'APP_NAME' => getenv('APP_NAME') ?: 'Nourish',
  'APP_ENV' => getenv('APP_ENV') ?: 'local',
  'APP_DEBUG' => $toBool((string) (getenv('APP_DEBUG') ?: 'true'), true),
  'APP_URL' => getenv('APP_URL') ?: 'http://localhost:8000',
  'APP_TIMEZONE' => getenv('APP_TIMEZONE') ?: 'Asia/Phnom_Penh',

  'DB_HOST' => getenv('DB_HOST') ?: '127.0.0.1',
  'DB_PORT' => $toInt((string) (getenv('DB_PORT') ?: '3306'), 3306),
  'DB_DATABASE' => getenv('DB_DATABASE') ?: 'grocery_delivery',
  'DB_USERNAME' => getenv('DB_USERNAME') ?: 'root',
  'DB_PASSWORD' => getenv('DB_PASSWORD') ?: '',
  'DB_CHARSET' => getenv('DB_CHARSET') ?: 'utf8mb4',
];
