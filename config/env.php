<?php

declare(strict_types=1);

if (!defined('BASE_PATH')) {
  define('BASE_PATH', dirname(__DIR__));
}

if (!class_exists('App\\Core\\Env')) {
  require_once BASE_PATH . '/app/Core/Env.php';
}

App\Core\Env::load(BASE_PATH . '/.env');
