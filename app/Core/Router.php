<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
  /** @var array<string, array<int, array{pattern:string, handler:callable|array{string,string}|Closure, middleware:array<int, mixed>}>> */
  private array $routes = [
    'GET' => [],
    'POST' => [],
  ];

  public function __construct(private readonly App $app)
  {
  }

  /** @param array<int, mixed> $middleware */
  public function get(string $pattern, callable|array $handler, array $middleware = []): void
  {
    $this->routes['GET'][] = ['pattern' => $pattern, 'handler' => $handler, 'middleware' => $middleware];
  }

  /** @param array<int, mixed> $middleware */
  public function post(string $pattern, callable|array $handler, array $middleware = []): void
  {
    $this->routes['POST'][] = ['pattern' => $pattern, 'handler' => $handler, 'middleware' => $middleware];
  }

  public function dispatch(Request $request): void
  {
    $method = $request->method();
    $path = $request->path();

    foreach ($this->routes[$method] ?? [] as $route) {
      $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
      $regex = '#^' . $regex . '$#';

      if (!preg_match($regex, $path, $matches)) {
        continue;
      }

      $params = array_filter(
        $matches,
        static fn(string|int $key): bool => is_string($key),
        ARRAY_FILTER_USE_KEY
      );

      $this->runMiddleware($route['middleware'] ?? [], $request, $params);

      $handler = $route['handler'];

      if (is_array($handler)) {
        [$controllerClass, $action] = $handler;
        $controller = new $controllerClass($this->app);
        $controller->$action($request, $params);
        return;
      }

      $handler($request, $params);
      return;
    }

    Response::abort(404, 'Page not found');
  }

  /** @param array<int, mixed> $middleware */
  private function runMiddleware(array $middleware, Request $request, array $params): void
  {
    foreach ($middleware as $entry) {
      if (is_callable($entry)) {
        $entry($request, $params, $this->app);
        continue;
      }

      if (!is_array($entry)) {
        continue;
      }

      $class = $entry[0] ?? null;
      $arguments = $entry[1] ?? [];

      if (!is_string($class) || !class_exists($class)) {
        continue;
      }

      if (!is_array($arguments)) {
        $arguments = [$arguments];
      }

      $instance = new $class();

      if (!method_exists($instance, 'handle')) {
        continue;
      }

      $instance->handle($request, ...$arguments);
    }
  }
}
