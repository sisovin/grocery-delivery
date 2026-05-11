# Authentication Reference — Sessions, Argon2id, CSRF, JWT, Refresh Tokens

## Table of Contents

1. [Scope and Auth Strategy](#1-scope-and-auth-strategy)
2. [Recommended Defaults](#2-recommended-defaults)
3. [Password Storage with Argon2id](#3-password-storage-with-argon2id)
4. [Session Login Flow](#4-session-login-flow)
5. [CSRF Requirements for Auth Flows](#5-csrf-requirements-for-auth-flows)
6. [JWT Access Tokens](#6-jwt-access-tokens)
7. [Refresh Token Rotation](#7-refresh-token-rotation)
8. [Hybrid Auth: Session for Web, JWT for API](#8-hybrid-auth-session-for-web-jwt-for-api)
9. [Database Tables](#9-database-tables)
10. [Implementation Checklist](#10-implementation-checklist)

---

## 1. Scope and Auth Strategy

Authentication answers one question: who is the caller?

In this stack, use one of these patterns on purpose:

- Server-rendered web app: session authentication with CSRF protection
- JSON API or mobile client: short-lived JWT access token plus refresh token rotation
- Mixed app: sessions for browser pages, JWT for API endpoints

Do not force JWT into a traditional server-rendered PHP app unless there is an actual API consumer that needs it.

---

## 2. Recommended Defaults

Use these defaults unless the product has a strong reason to do something else:

- Password hashing: `PASSWORD_ARGON2ID`
- Web login: session cookie with `HttpOnly`, `SameSite=Lax`, `Secure` in HTTPS
- State-changing forms: CSRF token required on every POST, PUT, PATCH, DELETE
- API auth: access token lifetime 5 to 15 minutes
- Refresh tokens: opaque random tokens, stored hashed in the database, rotated on every refresh
- Login throttling: required
- Session ID regeneration on successful login: required
- Separate revocation paths for sessions and refresh tokens: required

---

## 3. Password Storage with Argon2id

Argon2id is the preferred password hashing algorithm for new systems.

```php
<?php declare(strict_types=1);

function hashPassword(string $plainPassword): string
{
    return password_hash(
        $plainPassword,
        PASSWORD_ARGON2ID,
        [
            'memory_cost' => 64 * 1024,
            'time_cost'   => 4,
            'threads'     => 2,
        ]
    );
}

function verifyPassword(string $plainPassword, string $storedHash): bool
{
    return password_verify($plainPassword, $storedHash);
}

function passwordNeedsUpgrade(string $storedHash): bool
{
    return password_needs_rehash(
        $storedHash,
        PASSWORD_ARGON2ID,
        [
            'memory_cost' => 64 * 1024,
            'time_cost'   => 4,
            'threads'     => 2,
        ]
    );
}
```

On successful login, rehash if the stored parameters are outdated.

```php
if (verifyPassword($password, $user['password_hash'])) {
    if (passwordNeedsUpgrade($user['password_hash'])) {
        $newHash = hashPassword($password);
        $stmt = $pdo->prepare('UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'hash' => $newHash,
            'id'   => (int) $user['id'],
        ]);
    }
}
```

Never:

- store plaintext passwords
- store reversible encrypted passwords
- build your own hashing scheme
- compare password hashes manually

---

## 4. Session Login Flow

Use this for browser-based PHP pages.

### Login controller outline

```php
<?php declare(strict_types=1);

function attemptLogin(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare(
        'SELECT id, email, password_hash, is_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute(['email' => strtolower(trim($email))]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || !(bool) $user['is_active']) {
        return false;
    }

    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }

    session_regenerate_id(true);

    $_SESSION['auth'] = [
        'user_id'    => (int) $user['id'],
        'email'      => (string) $user['email'],
        'issued_at'  => time(),
        'last_seen'  => time(),
    ];

    return true;
}
```

### Session guard helper

```php
<?php declare(strict_types=1);

function requireUser(): array
{
    if (!isset($_SESSION['auth']) || !is_array($_SESSION['auth'])) {
        header('Location: /login');
        exit;
    }

    $_SESSION['auth']['last_seen'] = time();
    return $_SESSION['auth'];
}
```

### Logout flow

```php
<?php declare(strict_types=1);

function logout(): never
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            (bool) $params['secure'],
            (bool) $params['httponly']
        );
    }

    session_destroy();
    header('Location: /login');
    exit;
}
```

---

## 5. CSRF Requirements for Auth Flows

Every state-changing browser request needs a CSRF token, including:

- login
- logout
- register
- password reset request
- password reset submission
- MFA verification submit
- refresh token endpoint if called from browser cookies

Generate once per session and verify using `hash_equals()`.

```php
<?php declare(strict_types=1);

function csrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function requireValidCsrf(string $submittedToken): void
{
    $storedToken = $_SESSION['csrf_token'] ?? null;

    if (!is_string($storedToken) || !hash_equals($storedToken, $submittedToken)) {
        http_response_code(419);
        exit('CSRF validation failed');
    }
}
```

For server-rendered forms:

```php
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
```

For JavaScript requests:

```html
<meta name="csrf-token" content="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
```

---

## 6. JWT Access Tokens

Use JWT access tokens when an API consumer needs stateless request authentication.

### JWT payload guidance

Keep payloads small. Include only what is needed for request evaluation.

Recommended claims:

- `iss`: issuer
- `aud`: audience
- `sub`: user ID
- `jti`: token ID
- `iat`: issued at
- `nbf`: not before
- `exp`: expiration time
- `scope` or `roles`: minimal access context if needed

Do not put secrets, password hashes, or sensitive profile data in JWT payloads.

### Access token generation

Use a maintained library. Do not hand-roll JWT signing logic.

```php
<?php declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createAccessToken(int $userId, array $roles, string $jwtSecret): string
{
    $now = time();

    $payload = [
        'iss'   => 'ai-executive-platform',
        'aud'   => 'aep-api',
        'sub'   => (string) $userId,
        'jti'   => bin2hex(random_bytes(16)),
        'iat'   => $now,
        'nbf'   => $now,
        'exp'   => $now + 900,
        'roles' => array_values($roles),
    ];

    return JWT::encode($payload, $jwtSecret, 'HS256');
}

function decodeAccessToken(string $jwt, string $jwtSecret): object
{
    return JWT::decode($jwt, new Key($jwtSecret, 'HS256'));
}
```

### API bearer extraction

```php
<?php declare(strict_types=1);

function bearerToken(): ?string
{
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
        return null;
    }

    return trim($matches[1]);
}
```

JWT rules:

- short TTL only
- validate signature, issuer, audience, time claims
- reject unsigned or algorithm-switched tokens
- do not store long-lived access tokens in `localStorage` unless the threat model explicitly accepts XSS risk

---

## 7. Refresh Token Rotation

Refresh tokens should be opaque random strings, not JWTs by default.

Why:

- easier revocation
- easier server-side rotation tracking
- easier theft detection

### Storage model

Store this server-side:

- token hash
- user ID
- expires at
- created at
- rotated at
- revoked at
- replaced by token ID
- user agent and IP metadata if useful for review

### Generation and storage

```php
<?php declare(strict_types=1);

function issueRefreshToken(PDO $pdo, int $userId): string
{
    $plainToken = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $plainToken);

    $stmt = $pdo->prepare(
        'INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at)
         VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())'
    );
    $stmt->execute([
        'user_id'    => $userId,
        'token_hash' => $tokenHash,
    ]);

    return $plainToken;
}
```

### Rotation flow

```php
<?php declare(strict_types=1);

function rotateRefreshToken(PDO $pdo, string $presentedToken): ?string
{
    $presentedHash = hash('sha256', $presentedToken);

    $stmt = $pdo->prepare(
        'SELECT id, user_id, expires_at, revoked_at
         FROM refresh_tokens
         WHERE token_hash = :token_hash
         LIMIT 1'
    );
    $stmt->execute(['token_hash' => $presentedHash]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row || $row['revoked_at'] !== null || strtotime((string) $row['expires_at']) < time()) {
        return null;
    }

    $pdo->beginTransaction();

    try {
        $newToken = bin2hex(random_bytes(32));
        $newHash = hash('sha256', $newToken);

        $revoke = $pdo->prepare(
            'UPDATE refresh_tokens
             SET revoked_at = NOW(), rotated_at = NOW()
             WHERE id = :id'
        );
        $revoke->execute(['id' => (int) $row['id']]);

        $insert = $pdo->prepare(
            'INSERT INTO refresh_tokens (user_id, token_hash, expires_at, created_at)
             VALUES (:user_id, :token_hash, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())'
        );
        $insert->execute([
            'user_id'    => (int) $row['user_id'],
            'token_hash' => $newHash,
        ]);

        $pdo->commit();
        return $newToken;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

If an already-rotated refresh token appears again, treat it as suspicious and revoke the whole token family or the user session set.

---

## 8. Hybrid Auth: Session for Web, JWT for API

This is usually the best fit for PHP MVC apps that also expose APIs.

### Recommended split

- Web pages under clean paths like `/login`, `/employee`, and `/admin`: session auth + CSRF
- API routes under `/api/...`: bearer access token + refresh endpoint

### Why this split works

- browser pages keep simple PHP session semantics
- APIs remain consumable by mobile apps, workers, or external clients
- CSRF and cookie handling stay contained to web flows

Do not mix session and bearer evaluation in the same middleware unless there is a clear reason.

---

## 9. Database Tables

Typical authentication tables:

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE refresh_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    rotated_at DATETIME NULL,
    revoked_at DATETIME NULL,
    replaced_by_token_id BIGINT UNSIGNED NULL,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    INDEX idx_refresh_tokens_user_id (user_id),
    INDEX idx_refresh_tokens_expires_at (expires_at),
    CONSTRAINT fk_refresh_tokens_user FOREIGN KEY (user_id) REFERENCES users(id)
);
```

If you keep persistent browser sessions server-side, also maintain a `user_sessions` table for auditability and forced logout.

---

## 10. Implementation Checklist

- [ ] Use Argon2id for new password hashes
- [ ] Rehash on login when parameters change
- [ ] Regenerate session ID after successful login
- [ ] Protect every state-changing browser auth action with CSRF
- [ ] Rate-limit login and password reset endpoints
- [ ] Use short-lived JWT access tokens only where APIs need them
- [ ] Use opaque refresh tokens with hashing and rotation
- [ ] Revoke refresh token families on suspected replay
- [ ] Log login, logout, refresh, revoke, and failed auth events
- [ ] Keep authentication and authorization logic separate
