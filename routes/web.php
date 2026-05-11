<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\PromptController;
use App\Core\Router;

return static function (Router $router): void {
  $router->get('/', [HomeController::class, 'index']);
  $router->get('/products/{id}', [ProductController::class, 'show']);

  $router->get('/admin', [DashboardController::class, 'admin']);
  $router->get('/customer', [DashboardController::class, 'customer']);
  $router->get('/supplier', [DashboardController::class, 'supplier']);

  $router->post('/api/prompt/generate', [PromptController::class, 'generate']);
};
