<?php

declare(strict_types=1);

namespace App\Core;

use Closure;

final class Router
{
  /** @var array<string, array<int, array{pattern:string, handler:callable|array{string,string}|Closure}>> */
  private array $routes = [
    'GET' => [],
    'POST' => [],
  ];

  public function __construct(private readonly App $app)
  {
  }

  public function get(string $pattern, callable|array $handler): void
  {
    $this->routes['GET'][] = ['pattern' => $pattern, 'handler' => $handler];
  }

  public function post(string $pattern, callable|array $handler): void
  {
    $this->routes['POST'][] = ['pattern' => $pattern, 'handler' => $handler];
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
}
