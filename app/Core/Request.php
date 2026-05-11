<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
  /** @param array<string, string> $query */
  /** @param array<string, mixed> $body */
  private function __construct(
    private readonly string $method,
    private readonly string $path,
    private readonly array $query,
    private readonly array $body
  ) {
  }

  public static function capture(): self
  {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    $body = $_POST;
    if (empty($body)) {
      $raw = file_get_contents('php://input');
      if ($raw !== false && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
          $body = $decoded;
        }
      }
    }

    return new self(
      strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
      rtrim($path, '/') === '' ? '/' : rtrim($path, '/'),
      $_GET,
      $body
    );
  }

  public function method(): string
  {
    return $this->method;
  }

  public function path(): string
  {
    return $this->path;
  }

  public function input(string $key, mixed $default = null): mixed
  {
    return $this->body[$key] ?? $this->query[$key] ?? $default;
  }

  /** @return array<string, mixed> */
  public function all(): array
  {
    return array_merge($this->query, $this->body);
  }
}
