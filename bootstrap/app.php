<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
  $prefix = 'App\\';
  $baseDir = BASE_PATH . '/app/';

  if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
    return;
  }

  $relativeClass = substr($class, strlen($prefix));
  $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

  if (is_file($file)) {
    require $file;
  }
});

require BASE_PATH . '/app/Core/helpers.php';

require BASE_PATH . '/config/env.php';

$constants = require BASE_PATH . '/config/constants.php';
foreach ($constants as $name => $value) {
  if (!defined($name)) {
    define($name, $value);
  }
}

$app = new App\Core\App([
  'app' => require BASE_PATH . '/config/app.php',
  'database' => require BASE_PATH . '/config/database.php',
]);

$router = $app->router();
$routes = require BASE_PATH . '/routes/web.php';
$routes($router);
