<?php

declare(strict_types=1);

namespace App\Models;

final class Product
{
  public function __construct(
    public readonly int $id,
    public readonly string $name,
    public readonly string $origin,
    public readonly ?string $deal,
    public readonly float $price,
    public readonly string $description,
    public readonly bool $isOrganic,
    public readonly string $province
  ) {
  }

  /** @param array<string, mixed> $row */
  public static function fromArray(array $row): self
  {
    return new self(
      (int) ($row['id'] ?? 0),
      (string) ($row['name'] ?? ''),
      (string) ($row['origin'] ?? ''),
      isset($row['deal']) ? (string) $row['deal'] : null,
      (float) ($row['price'] ?? 0),
      (string) ($row['description'] ?? ''),
      (bool) ($row['is_organic'] ?? false),
      (string) ($row['province'] ?? '')
    );
  }
}
