<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Services\AuthService;

final class AuthController extends Controller
{
  public function showLogin(Request $request): void
  {
    $auth = new AuthService();
    $user = $auth->user();

    if ($user !== null) {
      $this->redirect($auth->dashboardPathForRole($user['role']));
      return;
    }

    $this->view('auth/login', [
      'title' => 'Login | Nourish',
      'error' => Session::consumeFlash('auth_error'),
      'status' => Session::consumeFlash('auth_status'),
    ]);
  }

  public function login(Request $request): void
  {
    if (!Csrf::validate((string) $request->input('_token', ''))) {
      Session::flash('auth_error', 'Invalid CSRF token.');
      $this->redirect('/login');
      return;
    }

    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');

    $auth = new AuthService();
    if (!$auth->login($email, $password)) {
      Session::flash('auth_error', 'Invalid credentials.');
      $this->redirect('/login');
      return;
    }

    $user = $auth->user();
    if ($user === null) {
      Session::flash('auth_error', 'Unable to start session.');
      $this->redirect('/login');
      return;
    }

    $this->redirect($auth->dashboardPathForRole($user['role']));
  }

  public function showRegister(Request $request): void
  {
    $this->view('auth/register', [
      'title' => 'Register | Nourish',
      'error' => Session::consumeFlash('auth_error'),
    ]);
  }

  public function register(Request $request): void
  {
    if (!Csrf::validate((string) $request->input('_token', ''))) {
      Session::flash('auth_error', 'Invalid CSRF token.');
      $this->redirect('/register');
      return;
    }

    $name = trim((string) $request->input('name', ''));
    $email = trim((string) $request->input('email', ''));
    $password = (string) $request->input('password', '');
    $passwordConfirmation = (string) $request->input('password_confirmation', '');
    $role = trim((string) $request->input('role', 'customer'));

    if ($name === '' || $email === '' || $password === '') {
      Session::flash('auth_error', 'Please fill all required fields.');
      $this->redirect('/register');
      return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      Session::flash('auth_error', 'Please enter a valid email address.');
      $this->redirect('/register');
      return;
    }

    if ($password !== $passwordConfirmation) {
      Session::flash('auth_error', 'Passwords do not match.');
      $this->redirect('/register');
      return;
    }

    if (strlen($password) < 8) {
      Session::flash('auth_error', 'Password must be at least 8 characters.');
      $this->redirect('/register');
      return;
    }

    $auth = new AuthService();
    $result = $auth->register($name, $email, $password, $role);

    if (!$result['ok']) {
      Session::flash('auth_error', (string) $result['error']);
      $this->redirect('/register');
      return;
    }

    $user = $auth->user();
    if ($user === null) {
      Session::flash('auth_error', 'Unable to start session.');
      $this->redirect('/login');
      return;
    }

    $this->redirect($auth->dashboardPathForRole($user['role']));
  }

  public function logout(Request $request): void
  {
    if (!Csrf::validate((string) $request->input('_token', ''))) {
      Session::flash('auth_error', 'Invalid CSRF token.');
      $this->redirect('/login');
      return;
    }

    (new AuthService())->logout();
    Session::flash('auth_status', 'Signed out successfully.');

    $this->redirect('/login');
  }
}
