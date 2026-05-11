<?php

declare(strict_types=1);

namespace App\Core;

use App\Services\AuthService;

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

  protected function redirect(string $to): void
  {
    Response::redirect($to);
  }

  /** @param array<int, string> $roles */
  protected function requireAuth(array $roles = []): array
  {
    $auth = new AuthService();
    $user = $auth->user();

    if ($user === null) {
      Session::flash('auth_error', 'Please sign in to continue.');
      $this->redirect('/login');
      exit;
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
      Response::abort(403, 'You do not have permission to access this page.');
    }

    return $user;
  }
}
