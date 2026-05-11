<?php

declare(strict_types=1);

namespace App\Models;

final class User
{
  public function __construct(
    public readonly int $id,
    public readonly string $name,
    public readonly string $email,
    public readonly string $passwordHash,
    public readonly string $role
  ) {
  }

  /** @param array<string, mixed> $row */
  public static function fromArray(array $row): self
  {
    return new self(
      (int) ($row['id'] ?? 0),
      (string) ($row['name'] ?? ''),
      (string) ($row['email'] ?? ''),
      (string) ($row['password_hash'] ?? ''),
      (string) ($row['role'] ?? 'customer')
    );
  }

  /** @return array{id:int,name:string,email:string,role:string} */
  public function toSessionPayload(): array
  {
    return [
      'id' => $this->id,
      'name' => $this->name,
      'email' => $this->email,
      'role' => $this->role,
    ];
  }
}
