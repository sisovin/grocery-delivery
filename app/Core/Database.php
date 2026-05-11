<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
  /** @var array<string, mixed> */
  private static array $config = [];

  private static ?PDO $pdo = null;

  /** @param array<string, mixed> $config */
  public static function boot(array $config): void
  {
    self::$config = $config;
  }

  public static function pdo(): PDO
  {
    if (self::$pdo instanceof PDO) {
      return self::$pdo;
    }

    $host = self::$config['host'] ?? '127.0.0.1';
    $port = self::$config['port'] ?? '3306';
    $database = self::$config['database'] ?? 'grocery_delivery';
    $charset = self::$config['charset'] ?? 'utf8mb4';

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

    try {
      self::$pdo = new PDO(
        $dsn,
        (string) (self::$config['username'] ?? 'root'),
        (string) (self::$config['password'] ?? ''),
        [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
          PDO::ATTR_EMULATE_PREPARES => false,
        ]
      );
    } catch (PDOException $exception) {
      throw new PDOException('Unable to connect to database: ' . $exception->getMessage(), (int) $exception->getCode());
    }

    return self::$pdo;
  }
}
