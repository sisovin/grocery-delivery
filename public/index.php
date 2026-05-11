<?php

declare(strict_types=1);

use App\Core\Session;

const BASE_PATH = __DIR__ . '/..';

require BASE_PATH . '/bootstrap/app.php';

Session::start();
$app->run();
