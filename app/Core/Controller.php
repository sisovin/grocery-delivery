<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
  public function __construct(protected App $app)
  {
  }

  /** @param array<string, mixed> $data */
  protected function view(string $view, array $data = []): void
  {
    View::render($view, $data);
  }

  /** @param array<string, mixed> $payload */
  protected function json(array $payload, int $status = 200): void
  {
    Response::json($payload, $status);
  }
}
