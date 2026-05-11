<?php

declare(strict_types=1);

namespace App\Core;

final class App
{
  private Router $router;

  /** @var array<string, mixed> */
  private array $config;

  /** @param array<string, mixed> $config */
  public function __construct(array $config)
  {
    $this->config = $config;
    $this->router = new Router($this);
  }

  public function router(): Router
  {
    return $this->router;
  }

  public function config(string $group, ?string $key = null, mixed $default = null): mixed
  {
    if (!array_key_exists($group, $this->config)) {
      return $default;
    }

    if ($key === null) {
      return $this->config[$group];
    }

    return $this->config[$group][$key] ?? $default;
  }

  public function run(): void
  {
    Database::boot($this->config('database'));

    $request = Request::capture();
    $this->router->dispatch($request);
  }
}
