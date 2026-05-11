<?php

declare(strict_types=1);

use App\Controllers\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\ProductController;
use App\Controllers\PromptController;
use App\Core\Middleware\AuthRoleMiddleware;
use App\Core\Router;

return static function (Router $router): void {
  $router->get('/', [HomeController::class, 'index']);
  $router->get('/products/{id}', [ProductController::class, 'show']);

  $router->get('/login', [AuthController::class, 'showLogin']);
  $router->post('/login', [AuthController::class, 'login']);
  $router->get('/register', [AuthController::class, 'showRegister']);
  $router->post('/register', [AuthController::class, 'register']);
  $router->post('/logout', [AuthController::class, 'logout']);

  $router->get('/admin', [DashboardController::class, 'admin'], [[AuthRoleMiddleware::class, ['admin']]]);
  $router->get('/customer', [DashboardController::class, 'customer'], [[AuthRoleMiddleware::class, ['customer']]]);
  $router->get('/supplier', [DashboardController::class, 'supplier'], [[AuthRoleMiddleware::class, ['supplier']]]);

  $router->post('/api/prompt/generate', [PromptController::class, 'generate']);
};
