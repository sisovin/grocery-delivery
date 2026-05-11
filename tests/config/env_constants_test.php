<?php

declare(strict_types=1);

const BASE_PATH = __DIR__ . '/../..';

$assertions = 0;

$assertTrue = static function (bool $condition, string $message) use (&$assertions): void {
  $assertions++;
  if (!$condition) {
    fwrite(STDERR, "Assertion failed: {$message}" . PHP_EOL);
    exit(1);
  }
};

$assertSame = static function (mixed $expected, mixed $actual, string $message) use (&$assertions): void {
  $assertions++;
  if ($expected !== $actual) {
    $expectedValue = var_export($expected, true);
    $actualValue = var_export($actual, true);
    fwrite(STDERR, "Assertion failed: {$message}. Expected {$expectedValue}, got {$actualValue}" . PHP_EOL);
    exit(1);
  }
};

require BASE_PATH . '/config/env.php';

$assertTrue(getenv('APP_NAME') !== false, 'APP_NAME should be available after loading config/env.php');
$assertTrue(getenv('DB_HOST') !== false, 'DB_HOST should be available after loading config/env.php');

$constants = require BASE_PATH . '/config/constants.php';
$legacyConstants = require BASE_PATH . '/configs/constants.php';

$assertSame($constants, $legacyConstants, 'configs/constants.php should proxy config/constants.php values');

$requiredKeys = [
  'APP_NAME',
  'APP_ENV',
  'APP_DEBUG',
  'APP_URL',
  'APP_TIMEZONE',
  'DB_HOST',
  'DB_PORT',
  'DB_DATABASE',
  'DB_USERNAME',
  'DB_PASSWORD',
  'DB_CHARSET',
];

foreach ($requiredKeys as $key) {
  $assertTrue(array_key_exists($key, $constants), "Missing constant key in config/constants.php: {$key}");
}

$assertSame((string) getenv('APP_NAME'), $constants['APP_NAME'], 'APP_NAME should match loaded environment value');
$assertSame((string) getenv('APP_ENV'), $constants['APP_ENV'], 'APP_ENV should match loaded environment value');
$assertSame((int) getenv('DB_PORT'), $constants['DB_PORT'], 'DB_PORT should be normalized as int');
$assertTrue(is_bool($constants['APP_DEBUG']), 'APP_DEBUG should be normalized as bool');

foreach ($constants as $name => $value) {
  if (!defined($name)) {
    define($name, $value);
  }
}

$appConfig = require BASE_PATH . '/config/app.php';
$dbConfig = require BASE_PATH . '/config/database.php';

$assertSame(APP_NAME, $appConfig['name'], 'config/app.php should use APP_NAME constant');
$assertSame(APP_ENV, $appConfig['env'], 'config/app.php should use APP_ENV constant');
$assertSame(APP_DEBUG, $appConfig['debug'], 'config/app.php should use APP_DEBUG constant');
$assertSame(APP_URL, $appConfig['url'], 'config/app.php should use APP_URL constant');
$assertSame(APP_TIMEZONE, $appConfig['timezone'], 'config/app.php should use APP_TIMEZONE constant');

$assertSame(DB_HOST, $dbConfig['host'], 'config/database.php should use DB_HOST constant');
$assertSame(DB_PORT, $dbConfig['port'], 'config/database.php should use DB_PORT constant');
$assertSame(DB_DATABASE, $dbConfig['database'], 'config/database.php should use DB_DATABASE constant');
$assertSame(DB_USERNAME, $dbConfig['username'], 'config/database.php should use DB_USERNAME constant');
$assertSame(DB_PASSWORD, $dbConfig['password'], 'config/database.php should use DB_PASSWORD constant');
$assertSame(DB_CHARSET, $dbConfig['charset'], 'config/database.php should use DB_CHARSET constant');

fwrite(STDOUT, "env/constants tests passed ({$assertions} assertions)." . PHP_EOL);
