<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Session;
use App\Repositories\UserRepository;

final class AuthService
{
  private const SESSION_KEY = 'auth_user';

  private UserRepository $users;

  public function __construct()
  {
    $this->users = new UserRepository();
  }

  /** @return array{id:int,name:string,email:string,role:string}|null */
  public function user(): ?array
  {
    $sessionUser = Session::get(self::SESSION_KEY);

    if (!is_array($sessionUser) || !isset($sessionUser['id'])) {
      return null;
    }

    $fresh = $this->users->findById((int) $sessionUser['id']);
    if ($fresh === null) {
      $this->logout();
      return null;
    }

    $payload = $fresh->toSessionPayload();
    Session::put(self::SESSION_KEY, $payload);

    return $payload;
  }

  public function check(): bool
  {
    return $this->user() !== null;
  }

  /** @param array<int, string> $roles */
  public function hasRole(array $roles): bool
  {
    $user = $this->user();
    return $user !== null && in_array($user['role'], $roles, true);
  }

  public function login(string $email, string $password): bool
  {
    $user = $this->users->findByEmail($email);
    if ($user === null || !password_verify($password, $user->passwordHash)) {
      return false;
    }

    Session::regenerate();
    Session::put(self::SESSION_KEY, $user->toSessionPayload());

    return true;
  }

  /** @return array{ok:bool,error:?string} */
  public function register(string $name, string $email, string $password, string $role): array
  {
    if (!in_array($role, ['customer', 'supplier'], true)) {
      return ['ok' => false, 'error' => 'Invalid role selected.'];
    }

    if ($this->users->emailExists($email)) {
      return ['ok' => false, 'error' => 'Email already exists.'];
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $user = $this->users->create($name, $email, $hash, $role);

    Session::regenerate();
    Session::put(self::SESSION_KEY, $user->toSessionPayload());

    return ['ok' => true, 'error' => null];
  }

  public function logout(): void
  {
    Session::forget(self::SESSION_KEY);
    Session::regenerate();
  }

  public function dashboardPathForRole(string $role): string
  {
    return match ($role) {
      'admin' => '/admin',
      'supplier' => '/supplier',
      default => '/customer',
    };
  }
}
