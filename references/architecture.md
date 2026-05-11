# Architecture Reference — MVC, Router, Request Lifecycle, REST APIs, Sessions & Auth

## Table of Contents
1. [Front Controller & Bootstrap](#1-front-controller--bootstrap)
2. [Router](#2-router)
3. [Request & Response](#3-request--response)
4. [Controller Pattern](#4-controller-pattern)
5. [Session Management & Flash Messages](#5-session-management--flash-messages)
6. [Authentication](#6-authentication)
7. [Middleware](#7-middleware)
8. [REST API Endpoints](#8-rest-api-endpoints)
9. [File Uploads](#9-file-uploads)
10. [View Rendering](#10-view-rendering)

---

## 1. Front Controller & Bootstrap

All HTTP requests hit `public/index.php`. The web root is `public/` only.

```php
<?php declare(strict_types=1);
// public/index.php

define('APP_START', microtime(true));
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

// Load environment
(Dotenv\Dotenv::createImmutable(BASE_PATH))->load();

// Register error handlers (see php-core.md)
registerErrorHandlers();

// Start session
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => isset($_SERVER['HTTPS']),
    'cookie_samesite' => 'Lax',
    'gc_maxlifetime'  => (int) ($_ENV['SESSION_LIFETIME'] ?? 7200),
]);

// Boot and run
$app = new \App\Core\Application();
$app->run();
```

```apache
# public/.htaccess — Apache
Options -Indexes
RewriteEngine On

# Block direct access to sensitive files
<FilesMatch "\.(env|log|sql|md|json|lock)$">
    Require all denied
</FilesMatch>

# Route all requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]
```

```nginx
# nginx — location block
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
    fastcgi_index index.php;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    include fastcgi_params;
}
```

---

## 2. Router

```php
<?php declare(strict_types=1);
namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewareGroups = [];

    public function get(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->addRoute('GET', $path, $handler, $middleware);
    }

    public function post(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->addRoute('POST', $path, $handler, $middleware);
    }

    public function put(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->addRoute('PUT', $path, $handler, $middleware);
    }

    public function patch(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, array|callable $handler, array $middleware = []): self
    {
        return $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    private function addRoute(string $method, string $path, array|callable $handler, array $middleware): self
    {
        // Convert path params to regex: /users/{id} → /users/(?P<id>[^/]+)
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        $pattern = '#^' . $pattern . '$#';

        $this->routes[] = compact('method', 'path', 'pattern', 'handler', 'middleware');
        return $this;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $uri    = $request->path();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (!preg_match($route['pattern'], $uri, $matches)) continue;

            // Extract named params
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setParams($params);

            // Run middleware
            foreach ($route['middleware'] as $middlewareClass) {
                (new $middlewareClass())->handle($request);
            }

            // Call handler
            if (is_callable($route['handler'])) {
                ($route['handler'])($request);
            } else {
                [$controllerClass, $method] = $route['handler'];
                (new $controllerClass())->{$method}($request);
            }
            return;
        }

        throw new \App\Exceptions\NotFoundException('Page', 0);
    }
}

// routes/web.php
<?php declare(strict_types=1);

use App\Controllers\{AuthController, UserController, PostController, HomeController};
use App\Middleware\{AuthMiddleware, GuestMiddleware, CsrfMiddleware};

$router = new \App\Core\Router();

// Public routes
$router->get('/',             [HomeController::class, 'index']);
$router->get('/login',        [AuthController::class, 'showLogin'],    [GuestMiddleware::class]);
$router->post('/login',       [AuthController::class, 'login'],        [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/register',     [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register',    [AuthController::class, 'register'],     [GuestMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout',      [AuthController::class, 'logout'],       [CsrfMiddleware::class]);

// Protected routes
$router->get('/dashboard',          [HomeController::class, 'dashboard'],    [AuthMiddleware::class]);
$router->get('/users',              [UserController::class, 'index'],        [AuthMiddleware::class]);
$router->get('/users/create',       [UserController::class, 'create'],       [AuthMiddleware::class]);
$router->post('/users',             [UserController::class, 'store'],        [AuthMiddleware::class, CsrfMiddleware::class]);
$router->get('/users/{id}',         [UserController::class, 'show'],         [AuthMiddleware::class]);
$router->get('/users/{id}/edit',    [UserController::class, 'edit'],         [AuthMiddleware::class]);
$router->post('/users/{id}',        [UserController::class, 'update'],       [AuthMiddleware::class, CsrfMiddleware::class]);
$router->post('/users/{id}/delete', [UserController::class, 'destroy'],      [AuthMiddleware::class, CsrfMiddleware::class]);

// API routes (JSON)
$router->get('/api/users',          [UserController::class, 'apiIndex'],     [AuthMiddleware::class]);
$router->get('/api/users/{id}',     [UserController::class, 'apiShow'],      [AuthMiddleware::class]);

return $router;
```

---

## 3. Request & Response

```php
<?php declare(strict_types=1);
namespace App\Core;

class Request
{
    private array $params = [];

    public function method(): string
    {
        // Support POST method override via _method hidden field or X-HTTP-Method-Override header
        $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
        if ($override && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
            return strtoupper($override);
        }
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function path(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return rtrim($uri, '/') ?: '/';
    }

    public function isJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();
        return $body[$key] ?? $_GET[$key] ?? $default;
    }

    public function body(): array
    {
        static $parsed = null;
        if ($parsed !== null) return $parsed;

        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $parsed = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $parsed = $_POST;
        }
        return $parsed;
    }

    public function only(string ...$keys): array
    {
        return array_intersect_key($this->body(), array_flip($keys));
    }

    public function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    public function setParams(array $params): void { $this->params = $params; }
    public function param(string $key, mixed $default = null): mixed { return $this->params[$key] ?? $default; }

    public function ip(): string { return $_SERVER['REMOTE_ADDR'] ?? ''; }
    public function userAgent(): string { return $_SERVER['HTTP_USER_AGENT'] ?? ''; }
    public function header(string $name): ?string { return $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $name))] ?? null; }
}

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    public static function redirect(string $url, int $status = 302): void
    {
        http_response_code($status);
        header("Location: $url");
        exit;
    }

    public static function back(): void
    {
        self::redirect($_SERVER['HTTP_REFERER'] ?? '/');
    }

    public static function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        extract($data, EXTR_SKIP);
        $e = fn(string $v): string => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        include BASE_PATH . "/src/Views/$template.php";
        exit;
    }
}
```

---

## 4. Controller Pattern

```php
<?php declare(strict_types=1);
namespace App\Controllers;

use App\Core\{Request, Response};
use App\Models\UserModel;
use App\Services\UserService;
use App\Exceptions\{NotFoundException, ValidationException};

class UserController
{
    private UserModel $model;
    private UserService $service;

    public function __construct()
    {
        $this->model   = new UserModel();
        $this->service = new UserService($this->model);
    }

    // GET /users
    public function index(Request $request): void
    {
        $page    = (int) ($request->input('page', 1));
        $search  = $request->input('search', '');
        $results = $this->model->paginate($page, 20, ['search' => $search]);

        Response::view('users/index', [
            'title'   => 'Users',
            'results' => $results,
            'search'  => $search,
        ]);
    }

    // GET /users/{id}
    public function show(Request $request): void
    {
        $user = $this->model->findById((int) $request->param('id'))
            ?? throw new NotFoundException('User', (int) $request->param('id'));

        Response::view('users/show', ['user' => $user, 'title' => $user['name']]);
    }

    // POST /users
    public function store(Request $request): void
    {
        $data = $request->only('name', 'email', 'password', 'role');

        try {
            $userId = $this->service->createUser($data);
            $_SESSION['flash']['success'] = 'User created successfully.';
            Response::redirect("/users/$userId");
        } catch (ValidationException $e) {
            $_SESSION['flash']['error'] = 'Please fix the errors below.';
            $_SESSION['old'] = $data;
            $_SESSION['errors'] = $e->getErrors();
            Response::back();
        }
    }

    // GET /api/users — JSON endpoint
    public function apiIndex(Request $request): void
    {
        $users = $this->model->paginate((int) $request->input('page', 1));
        Response::json($users);
    }
}
```

---

## 5. Session Management & Flash Messages

```php
<?php declare(strict_types=1);
namespace App\Core;

class Session
{
    public static function flash(string $type, string $message): void
    {
        $_SESSION['flash'][$type] = $message;
    }

    public static function getFlash(string $type): ?string
    {
        $msg = $_SESSION['flash'][$type] ?? null;
        unset($_SESSION['flash'][$type]);
        return $msg;
    }

    public static function old(string $key, mixed $default = ''): mixed
    {
        $value = $_SESSION['old'][$key] ?? $default;
        return $value;
    }

    public static function clearOld(): void { unset($_SESSION['old'], $_SESSION['errors']); }

    public static function errors(): array { return $_SESSION['errors'] ?? []; }
    public static function error(string $field): ?string { return $_SESSION['errors'][$field] ?? null; }

    public static function set(string $key, mixed $value): void { $_SESSION[$key] = $value; }
    public static function get(string $key, mixed $default = null): mixed { return $_SESSION[$key] ?? $default; }
    public static function forget(string $key): void { unset($_SESSION[$key]); }
    public static function destroy(): void { session_destroy(); }
}
```

---

## 6. Authentication

```php
<?php declare(strict_types=1);
namespace App\Services;

use App\Core\Session;

class AuthService
{
    public function __construct(private \App\Models\UserModel $model) {}

    public function attempt(string $email, string $password): bool
    {
        $user = $this->model->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;  // Never reveal whether email or password was wrong
        }

        if ($user['deleted_at'] !== null) return false;

        // Regenerate session ID on login — prevents session fixation
        session_regenerate_id(true);

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['login_at']  = time();

        // Rehash if needed (e.g. cost factor changed)
        if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
            $this->model->updatePassword($user['id'], $password);
        }

        return true;
    }

    public function user(): ?array
    {
        if (!isset($_SESSION['user_id'])) return null;

        // Session timeout check
        $maxLifetime = (int) ($_ENV['SESSION_LIFETIME'] ?? 7200);
        if (isset($_SESSION['login_at']) && (time() - $_SESSION['login_at']) > $maxLifetime) {
            $this->logout();
            return null;
        }

        return [
            'id'   => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'],
            'role' => $_SESSION['user_role'],
        ];
    }

    public function check(): bool { return isset($_SESSION['user_id']); }

    public function logout(): void
    {
        session_unset();
        session_destroy();
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'   => isset($_SERVER['HTTPS']),
            'cookie_samesite' => 'Lax',
        ]);
        session_regenerate_id(true);
    }
}
```

---

## 7. Middleware

```php
<?php declare(strict_types=1);
namespace App\Middleware;

use App\Core\{Request, Response};

interface Middleware
{
    public function handle(Request $request): void;
}

class AuthMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['flash']['error'] = 'Please log in to continue.';
            $_SESSION['intended'] = $request->path();
            Response::redirect('/login');
        }
    }
}

class GuestMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (isset($_SESSION['user_id'])) {
            Response::redirect('/dashboard');
        }
    }
}

class CsrfMiddleware implements Middleware
{
    public function handle(Request $request): void
    {
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) return;

        $token = $request->input('csrf_token')
            ?? $request->header('X-CSRF-Token')
            ?? '';

        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(419);
            if ($request->isJson()) {
                Response::json(['error' => 'CSRF token mismatch'], 419);
            }
            die('CSRF token mismatch. Please go back and try again.');
        }
    }
}

// Generate CSRF token (call once per session in bootstrap)
function generateCsrfToken(): void
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}
```

---

## 8. REST API Endpoints

```php
<?php declare(strict_types=1);
// Full JSON API controller
namespace App\Controllers\Api;

use App\Core\{Request, Response};

class ApiController
{
    protected function success(mixed $data, string $message = 'OK', int $status = 200): void
    {
        Response::json(['success' => true, 'message' => $message, 'data' => $data], $status);
    }

    protected function error(string $message, int $status = 400, array $errors = []): void
    {
        Response::json(['success' => false, 'error' => $message, 'errors' => $errors], $status);
    }

    protected function paginated(array $results): void
    {
        Response::json(['success' => true, ...$results]);
    }
}

class UsersApiController extends ApiController
{
    public function index(Request $request): void
    {
        $page  = max(1, (int) $request->input('page', 1));
        $model = new \App\Models\UserModel();

        $this->paginated($model->paginate($page));
    }

    public function store(Request $request): void
    {
        $data = $request->only('name', 'email', 'password', 'role');
        $service = new \App\Services\UserService(new \App\Models\UserModel());

        try {
            $user = $service->createUser($data);
            $this->success($user, 'User created', 201);
        } catch (\App\Exceptions\ValidationException $e) {
            $this->error('Validation failed', 422, $e->getErrors());
        }
    }
}
```

---

## 9. File Uploads

```php
<?php declare(strict_types=1);
namespace App\Services;

class FileUploadService
{
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    private const MAX_SIZE = 2 * 1024 * 1024; // 2MB

    public function __construct(
        private string $uploadPath = BASE_PATH . '/storage/uploads'
    ) {}

    public function uploadImage(array $file, string $subdir = 'avatars'): string
    {
        $this->validateUpload($file);

        // Verify MIME type from actual file content, not extension
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new \InvalidArgumentException('Invalid file type');
        }

        // Generate safe random filename — never use original filename
        $extension = match($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
        };
        $filename = bin2hex(random_bytes(16)) . '.' . $extension;
        $dir      = $this->uploadPath . '/' . $subdir;

        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $destination = $dir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new \RuntimeException('Failed to save uploaded file');
        }

        return "/uploads/$subdir/$filename";
    }

    private function validateUpload(array $file): void
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(match($file['error'] ?? -1) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large',
                UPLOAD_ERR_NO_FILE => 'No file selected',
                default => 'Upload failed',
            });
        }
        if ($file['size'] > self::MAX_SIZE) {
            throw new \InvalidArgumentException('File exceeds 2MB limit');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new \InvalidArgumentException('Invalid upload');
        }
    }
}
```

---

## 10. View Rendering

```php
<!-- src/Views/layout/app.php — reusable layout -->
<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES) ?>">
    <title><?= htmlspecialchars($title ?? 'App') ?> — My App</title>
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="h-full bg-gray-50">
    <?php include __DIR__ . '/partials/nav.php'; ?>
    <main id="main-content" class="py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <!-- Flash messages -->
            <?php foreach (['success', 'error', 'warning'] as $type):
                $msg = \App\Core\Session::getFlash($type);
                if (!$msg) continue;
                $cls = match($type) {
                    'success' => 'bg-green-50 border-green-400 text-green-800',
                    'error'   => 'bg-red-50 border-red-400 text-red-800',
                    'warning' => 'bg-yellow-50 border-yellow-400 text-yellow-800',
                };
            ?>
                <div role="alert" class="mb-6 border-l-4 p-4 rounded-r-lg <?= $cls ?>">
                    <?= htmlspecialchars($msg) ?>
                </div>
            <?php endforeach; ?>

            <?= $content ?? '' ?>
        </div>
    </main>
    <?php include __DIR__ . '/partials/footer.php'; ?>
    <script type="module" src="/assets/js/app.js"></script>
</body>
</html>
```