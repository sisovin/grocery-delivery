<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\AuthService;

final class AuthRoleMiddleware
{
  /**
   * @param array<int, string> $roles
   */
  public function handle(Request $request, array $roles = []): void
  {
    $auth = new AuthService();
    $user = $auth->user();

    if ($user === null) {
      Session::flash('auth_error', 'Please sign in to continue.');
      Response::redirect('/login');
    }

    if ($roles !== [] && !in_array($user['role'], $roles, true)) {
      Response::abort(403, 'You do not have permission to access this page.');
    }
  }
}
