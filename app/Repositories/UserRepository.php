<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use App\Models\User;
use PDO;

final class UserRepository
{
  public function findByEmail(string $email): ?User
  {
    $stmt = Database::pdo()->prepare(
      'SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
    );
    $stmt->execute(['email' => $email]);
    $row = $stmt->fetch();

    return is_array($row) ? User::fromArray($row) : null;
  }

  public function findById(int $id): ?User
  {
    $stmt = Database::pdo()->prepare(
      'SELECT id, name, email, password_hash, role FROM users WHERE id = :id LIMIT 1'
    );
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return is_array($row) ? User::fromArray($row) : null;
  }

  public function emailExists(string $email): bool
  {
    $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);

    return (int) $stmt->fetchColumn() > 0;
  }

  public function create(string $name, string $email, string $passwordHash, string $role): User
  {
    $stmt = Database::pdo()->prepare(
      'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)'
    );
    $stmt->execute([
      'name' => $name,
      'email' => $email,
      'password_hash' => $passwordHash,
      'role' => $role,
    ]);

    return new User(
      (int) Database::pdo()->lastInsertId(),
      $name,
      $email,
      $passwordHash,
      $role
    );
  }

  public function seedDefaultUsers(string $password): int
  {
    $defaults = [
      ['System Admin', 'admin@nourish.local', 'admin'],
      ['Customer Demo', 'customer@nourish.local', 'customer'],
      ['Supplier Demo', 'supplier@nourish.local', 'supplier'],
    ];

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $count = 0;

    $pdo = Database::pdo();
    $pdo->beginTransaction();

    try {
      $stmt = $pdo->prepare(
        'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :password_hash, :role)
         ON DUPLICATE KEY UPDATE name = VALUES(name), password_hash = VALUES(password_hash), role = VALUES(role)'
      );

      foreach ($defaults as [$name, $email, $role]) {
        $stmt->execute([
          'name' => $name,
          'email' => $email,
          'password_hash' => $hash,
          'role' => $role,
        ]);
        $count++;
      }

      $pdo->commit();
    } catch (\Throwable $exception) {
      $pdo->rollBack();
      throw $exception;
    }

    return $count;
  }
}
