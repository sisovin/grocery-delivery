# Middleware Reference — AuthMiddleware, PermissionMiddleware, JwtMiddleware

## Table of Contents

1. [Scope](#1-scope)
2. [Middleware Design Rules](#2-middleware-design-rules)
3. [Shared Helpers](#3-shared-helpers)
4. [AuthMiddleware](#4-authmiddleware)
5. [PermissionMiddleware](#5-permissionmiddleware)
6. [JwtMiddleware](#6-jwtmiddleware)
7. [CsrfMiddleware Placement](#7-csrfmiddleware-placement)
8. [Using Middleware in This Project Style](#8-using-middleware-in-this-project-style)
9. [Failure Responses](#9-failure-responses)
10. [Implementation Checklist](#10-implementation-checklist)

---

## 1. Scope

This reference provides concrete middleware implementations for the native PHP MVC stack used in this repository.

It covers:

- `AuthMiddleware` for session-authenticated web routes
- `PermissionMiddleware` for RBAC permission checks
- `JwtMiddleware` for bearer token API routes

Use it with:

- `references/authentication.md`
- `references/authorization.md`
- `references/security.md`
- `references/architecture.md`

---

## 2. Middleware Design Rules

Middleware should:

- run before controller logic
- fail fast
- not render complex views directly unless the app architecture expects that
- attach the authenticated user context for later use
- be predictable and side-effect light

Do not bury business decisions inside middleware. Use it for request guards and shared access checks.

---

## 3. Shared Helpers

```php
<?php declare(strict_types=1);

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function abort(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    echo $message;
    exit;
}

function currentAuthUser(): ?array
{
    return isset($_SESSION['auth']) && is_array($_SESSION['auth'])
        ? $_SESSION['auth']
        : null;
}
```

If the project later adds a `Request` object with attributes, prefer attaching the user to the request rather than relying only on `$_SESSION` or globals.

---

## 4. AuthMiddleware

Use this for server-rendered browser routes protected by session auth.

```php
<?php declare(strict_types=1);

class AuthMiddleware
{
    public function handle(): void
    {
        $auth = currentAuthUser();

        if ($auth === null) {
            redirect('/login');
        }

        $_SESSION['auth']['last_seen'] = time();
    }
}
```

### Stricter version with session timeout

```php
<?php declare(strict_types=1);

class AuthMiddleware
{
    public function __construct(private int $maxIdleSeconds = 7200) {}

    public function handle(): void
    {
        $auth = currentAuthUser();

        if ($auth === null) {
            redirect('/login');
        }

        $lastSeen = isset($auth['last_seen']) ? (int) $auth['last_seen'] : 0;
        if ($lastSeen > 0 && (time() - $lastSeen) > $this->maxIdleSeconds) {
            $_SESSION = [];
            session_destroy();
            redirect('/login');
        }

        $_SESSION['auth']['last_seen'] = time();
    }
}
```

---

## 5. PermissionMiddleware

Use this after authentication to enforce explicit permissions.

```php
<?php declare(strict_types=1);

class PermissionMiddleware
{
    public function __construct(private string $requiredPermission) {}

    public function handle(PDO $pdo): void
    {
        $auth = currentAuthUser();
        if ($auth === null) {
            redirect('/login');
        }

        $stmt = $pdo->prepare(
            'SELECT DISTINCT p.permission_key
             FROM permissions p
             INNER JOIN role_permissions rp ON rp.permission_id = p.id
             INNER JOIN user_roles ur ON ur.role_id = rp.role_id
             WHERE ur.user_id = :user_id'
        );
        $stmt->execute(['user_id' => (int) $auth['user_id']]);

        $permissionKeys = array_map(
            static fn(array $row): string => (string) $row['permission_key'],
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );

        if (!in_array($this->requiredPermission, $permissionKeys, true)) {
            abort(403, 'Forbidden');
        }
    }
}
```

### Request-scoped optimization

If the app performs multiple permission checks in one request, load permissions once and cache them in a request container or static request-local store.

---

## 6. JwtMiddleware

Use this for API routes expecting `Authorization: Bearer <token>`.

```php
<?php declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JwtMiddleware
{
    public function __construct(
        private string $jwtSecret,
        private string $expectedIssuer,
        private string $expectedAudience,
    ) {}

    public function handle(): object
    {
        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
            abort(401, json_encode(['error' => 'Missing bearer token']));
        }

        try {
            $claims = JWT::decode(trim($matches[1]), new Key($this->jwtSecret, 'HS256'));
        } catch (ExpiredException) {
            abort(401, json_encode(['error' => 'Access token expired']));
        } catch (Throwable) {
            abort(401, json_encode(['error' => 'Invalid access token']));
        }

        if (($claims->iss ?? null) !== $this->expectedIssuer) {
            abort(401, json_encode(['error' => 'Invalid token issuer']));
        }

        if (($claims->aud ?? null) !== $this->expectedAudience) {
            abort(401, json_encode(['error' => 'Invalid token audience']));
        }

        return $claims;
    }
}
```

### JWT + permission scope enforcement

```php
<?php declare(strict_types=1);

function requireTokenScope(object $claims, string $requiredScope): void
{
    $scopes = isset($claims->scope) && is_array($claims->scope) ? $claims->scope : [];

    if (!in_array($requiredScope, $scopes, true)) {
        abort(403, json_encode(['error' => 'Insufficient scope']));
    }
}
```

For high-risk API actions, validate current permissions against the database as well, not just token scope.

---

## 7. CsrfMiddleware Placement

CSRF is separate from auth and JWT concerns.

Use CSRF middleware for:

- server-rendered forms using session cookies
- AJAX requests authenticated with browser cookies

Do not apply CSRF middleware to pure bearer-token API routes that do not rely on cookies.

---

## 8. Using Middleware in This Project Style

This repository currently uses a very lightweight `App` dispatcher rather than the richer router shown in `references/architecture.md`.

That means practical integration usually looks like one of these:

### Option A — call middleware inside controller actions

```php
<?php declare(strict_types=1);

class AdminController extends Controller
{
    public function index(): void
    {
        (new AuthMiddleware())->handle();

        $pdo = (new EmployeeModel())->getConnection();
        (new PermissionMiddleware('dashboard.view'))->handle($pdo);

        View::render('admin/index', ['layout' => 'admin']);
    }
}
```

### Option B — extend the dispatcher to map route metadata to middleware

If the project grows, push middleware orchestration into the routing layer rather than repeating it in every controller.

---

## 9. Failure Responses

Recommended defaults:

- unauthenticated browser route: redirect to login
- unauthorized browser route: HTTP 403 with a small error page
- unauthenticated API route: JSON 401
- unauthorized API route: JSON 403

Do not leak whether a user exists, which role was missing, or internal policy structure unless the product explicitly requires that detail.

---

## 10. Implementation Checklist

- [ ] Keep session auth and JWT auth separated by route type
- [ ] Run auth middleware before permission middleware
- [ ] Refresh `last_seen` or session activity only after auth success
- [ ] Return JSON errors for API middleware failures
- [ ] Validate token issuer and audience, not just signature
- [ ] Avoid repeating permission lookups within a single request
- [ ] Keep CSRF middleware on cookie-based state changes only
- [ ] Prefer routing-layer middleware orchestration as the app grows
- [ ] Do not place business rules in middleware
- [ ] Log authentication and authorization failures appropriately
