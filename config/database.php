<?php

declare(strict_types=1);

return [
  'host' => defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: '127.0.0.1'),
  'port' => defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '3306'),
  'database' => defined('DB_DATABASE') ? DB_DATABASE : (getenv('DB_DATABASE') ?: 'grocery_delivery'),
  'username' => defined('DB_USERNAME') ? DB_USERNAME : (getenv('DB_USERNAME') ?: 'root'),
  'password' => defined('DB_PASSWORD') ? DB_PASSWORD : (getenv('DB_PASSWORD') ?: ''),
  'charset' => defined('DB_CHARSET') ? DB_CHARSET : (getenv('DB_CHARSET') ?: 'utf8mb4'),
];
