<?php

declare(strict_types=1);

return [
  'name' => defined('APP_NAME') ? APP_NAME : (getenv('APP_NAME') ?: 'Nourish'),
  'env' => defined('APP_ENV') ? APP_ENV : (getenv('APP_ENV') ?: 'local'),
  'debug' => defined('APP_DEBUG') ? APP_DEBUG : ((getenv('APP_DEBUG') ?: 'true') === 'true'),
  'url' => defined('APP_URL') ? APP_URL : (getenv('APP_URL') ?: 'http://localhost:8000'),
  'timezone' => defined('APP_TIMEZONE') ? APP_TIMEZONE : (getenv('APP_TIMEZONE') ?: 'Asia/Phnom_Penh'),
];
