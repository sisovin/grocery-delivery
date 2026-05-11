# Security Reference — PHP Security, Input Validation, XSS/CSRF/SQLi, Headers

## Table of Contents

1. [Input Validation](#1-input-validation)
2. [Output Escaping (XSS Prevention)](#2-output-escaping-xss-prevention)
3. [SQL Injection Prevention](#3-sql-injection-prevention)
4. [CSRF Protection](#4-csrf-protection)
5. [Password Security](#5-password-security)
6. [HTTP Security Headers](#6-http-security-headers)
7. [CSP for This App Runtime](#7-csp-for-this-app-runtime)
8. [Cookie Strategy for This App Runtime](#8-cookie-strategy-for-this-app-runtime)
9. [Session Security](#9-session-security)
10. [File Upload Security](#10-file-upload-security)
11. [Rate Limiting](#11-rate-limiting)
12. [Security Checklist](#12-security-checklist)

---

## 1. Input Validation

**Validate every input at the entry point — before it touches the database or business logic.**

```php
<?php declare(strict_types=1);
namespace App\Services;

class Validator
{
    private array $errors = [];

    public function __construct(private array $data) {}

    public static function make(array $data): self { return new self($data); }

    public function validate(array $rules): self
    {
        foreach ($rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            foreach (explode('|', $ruleString) as $rule) {
                $this->applyRule($field, $value, $rule);
            }
        }
        return $this;
    }

    private function applyRule(string $field, mixed $value, string $rule): void
    {
        [$ruleName, $param] = array_pad(explode(':', $rule, 2), 2, null);

        match($ruleName) {
            'required'  => $this->check($field, !empty($value), 'The ' . $field . ' field is required'),
            'email'     => $this->check($field, filter_var($value, FILTER_VALIDATE_EMAIL) !== false, 'Invalid email address'),
            'min'       => $this->check($field, strlen((string) $value) >= (int) $param, "Minimum $param characters required"),
            'max'       => $this->check($field, strlen((string) $value) <= (int) $param, "Maximum $param characters allowed"),
            'numeric'   => $this->check($field, is_numeric($value), 'Must be a number'),
            'integer'   => $this->check($field, filter_var($value, FILTER_VALIDATE_INT) !== false, 'Must be an integer'),
            'url'       => $this->check($field, filter_var($value, FILTER_VALIDATE_URL) !== false, 'Invalid URL'),
            'in'        => $this->check($field, in_array($value, explode(',', $param ?? ''), true), "Invalid value"),
            'alpha'     => $this->check($field, ctype_alpha((string) $value), 'Only letters allowed'),
            'alphanum'  => $this->check($field, ctype_alnum((string) $value), 'Only letters and numbers allowed'),
            'regex'     => $this->check($field, (bool) preg_match($param, (string) $value), 'Invalid format'),
            'confirmed' => $this->check($field, $value === ($this->data[$field . '_confirmation'] ?? null), 'Confirmation does not match'),
            default     => throw new \InvalidArgumentException("Unknown validation rule: $ruleName"),
        };
    }

    private function check(string $field, bool $passes, string $message): void
    {
        if (!$passes) $this->errors[$field][] = $message;
    }

    public function passes(): bool { return empty($this->errors); }
    public function fails(): bool  { return !$this->passes(); }
    public function errors(): array { return $this->errors; }

    public function validated(): array
    {
        if ($this->fails()) {
            throw new \App\Exceptions\ValidationException($this->errors);
        }
        return $this->data;
    }
}

// Usage in controller/service
$validator = Validator::make($request->body())->validate([
    'name'                  => 'required|min:2|max:100',
    'email'                 => 'required|email|max:255',
    'password'              => 'required|min:8|max:128|confirmed',
    'role'                  => 'required|in:admin,editor,user',
]);

if ($validator->fails()) {
    throw new \App\Exceptions\ValidationException($validator->errors());
}
```

### Sanitization helpers

```php
// Sanitize for storage (trim and clean, but don't escape — escape at output)
function sanitizeString(string $value): string
{
    return trim(strip_tags($value));  // Remove HTML tags from plain-text fields
}

function sanitizeName(string $value): string
{
    return trim(preg_replace('/\s+/', ' ', strip_tags($value)));
}

function sanitizeEmail(string $value): string
{
    return strtolower(trim(filter_var($value, FILTER_SANITIZE_EMAIL)));
}

function sanitizeInt(mixed $value): int
{
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

function sanitizeUrl(string $value): string
{
    return filter_var(trim($value), FILTER_SANITIZE_URL);
}

// Allow specific HTML (user-generated rich content)
function sanitizeHtml(string $html): string
{
    // Use HTMLPurifier or strip to a safe allowlist
    $allowed = '<p><br><strong><em><ul><ol><li><a><h2><h3><h4><blockquote>';
    return strip_tags($html, $allowed);
}
```

---

## 2. Output Escaping (XSS Prevention)

**The golden rule: escape at the point of output, not at input.**

```php
// Define a global helper — use in every template
function e(string $value, int $flags = ENT_QUOTES | ENT_SUBSTITUTE): string
{
    return htmlspecialchars($value, $flags, 'UTF-8');
}

// ❌ XSS vulnerability — never do this
echo $_GET['name'];
echo $user['bio'];

// ✅ Safe output
echo e($_GET['name']);
echo e($user['bio']);

// ✅ In HTML attributes
<input value="<?= e($user['name']) ?>">
<div data-name="<?= e($user['name']) ?>">

// ✅ In URLs — use rawurlencode for path components
<a href="/search?q=<?= rawurlencode($query) ?>">

// ✅ JSON in <script> — use JSON_HEX flags to prevent </script> injection
<script>
const data = <?= json_encode($data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

// ✅ CSS context — never put user data in CSS (very hard to escape safely)
// ❌ Don't do: <div style="color: <?= $user['color'] ?>">

// Multiline safe output — for article body with allowed HTML
echo sanitizeHtml($post['content']);
```

---

## 3. SQL Injection Prevention

```php
// ❌ NEVER — string concatenation with user data
$sql = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";

// ❌ NEVER — even with escape functions (use proper prepared statements)
$email = mysqli_real_escape_string($conn, $_POST['email']);
$sql = "SELECT * FROM users WHERE email = '$email'";

// ✅ Always — PDO prepared statement with named params
$stmt = $db->prepare('SELECT id, name FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// ✅ Dynamic column names — whitelist approach (never parameterize identifiers)
function buildOrderClause(string $column, string $direction): string
{
    $allowedColumns = ['name', 'email', 'created_at', 'role'];
    $allowedDirs    = ['ASC', 'DESC'];

    $col = in_array($column, $allowedColumns, true) ? $column : 'created_at';
    $dir = in_array(strtoupper($direction), $allowedDirs, true) ? strtoupper($direction) : 'DESC';

    return "ORDER BY $col $dir";  // Safe — values come from whitelist, not user
}

// ✅ Dynamic WHERE with IN clause
function safeInClause(\PDO $db, string $table, string $column, array $ids): array
{
    if (empty($ids)) return [];
    $ids  = array_map('intval', $ids);  // Force integers
    $ph   = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM $table WHERE $column IN ($ph)");
    $stmt->execute($ids);
    return $stmt->fetchAll();
}
```

---

## 4. CSRF Protection

```php
<?php declare(strict_types=1);
// Generate token — call once at session start
function generateCsrfToken(): string
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify token — call before processing any POST/PUT/PATCH/DELETE
function verifyCsrfToken(string $submitted): bool
{
    if (!isset($_SESSION['csrf_token'])) return false;
    // hash_equals prevents timing attacks
    return hash_equals($_SESSION['csrf_token'], $submitted);
}

// Shorthand form field helper
function csrfField(): string
{
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
}

// In every HTML form that changes state
<form method="POST" action="/users">
    <?= csrfField() ?>
    <!-- form fields -->
</form>

// For AJAX — include token in meta and read in JS
<meta name="csrf-token" content="<?= e(generateCsrfToken()) ?>">

// Middleware validation (see architecture.md CsrfMiddleware)
```

---

## 5. Password Security

```php
// Prefer Argon2id for new systems
function hashPassword(string $password): string
{
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 64 * 1024,
        'time_cost'   => 4,
        'threads'     => 2,
    ]);
    // Bcrypt remains a compatibility fallback where Argon2id is unavailable.
}

// Verification — timing-safe
function verifyPassword(string $password, string $hash): bool
{
    return password_verify($password, $hash);
}

// Rehash check — update if cost factor changes
function rehashIfNeeded(\PDO $db, int $userId, string $password, string $currentHash): void
{
    if (password_needs_rehash($currentHash, PASSWORD_ARGON2ID, [
        'memory_cost' => 64 * 1024,
        'time_cost'   => 4,
        'threads'     => 2,
    ])) {
        $newHash = hashPassword($password);
        $stmt    = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([$newHash, $userId]);
    }
}

// Password strength validation
function validatePasswordStrength(string $password): array
{
    $errors = [];
    if (strlen($password) < 8)             $errors[] = 'At least 8 characters';
    if (strlen($password) > 128)            $errors[] = 'Too long (max 128)';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Must contain uppercase letter';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Must contain a number';
    return $errors;
}

// Secure token generation (for password reset, email verification, API keys)
function generateSecureToken(int $bytes = 32): string
{
    return bin2hex(random_bytes($bytes));  // 64 char hex string
}

// Password reset flow
function createPasswordResetToken(\PDO $db, string $email): ?string
{
    $user = /* find user by email */ null;
    if (!$user) return null;  // Return null even if not found — don't reveal whether email exists

    $token     = generateSecureToken();
    $tokenHash = hash('sha256', $token);   // Store hash, return plain token
    $expiry    = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token=VALUES(token), expires_at=VALUES(expires_at)');
    $stmt->execute([$email, $tokenHash, $expiry]);

    return $token;  // Send this in email link
}
```

---

## 6. HTTP Security Headers

Set these on every response:

```php
function setSecurityHeaders(): void
{
    // Content-Security-Policy — customize per app
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'nonce-" . generateNonce() . "'; style-src 'self' 'unsafe-inline' https://unpkg.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com");

    // Prevent clickjacking
    header('X-Frame-Options: DENY');

    // Prevent MIME sniffing
    header('X-Content-Type-Options: nosniff');

    // Force HTTPS (only in production)
    if ($_ENV['APP_ENV'] === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    // Control referrer information
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Disable browser features you don't need
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    // Remove PHP version from headers
    header_remove('X-Powered-By');
}

function generateNonce(): string
{
    static $nonce = null;
    return $nonce ??= base64_encode(random_bytes(16));
}

// Call in bootstrap, before any output
setSecurityHeaders();
```

---

## 7. CSP for This App Runtime

This project currently runs in two local modes:

- Laragon subdirectory mode: `http://localhost/ai-executive-platform/public`
- PHP built-in server mode: `http://localhost:8000`

It currently serves:

- local JS from `public/assets/js/app.js`
- local CSS from `public/assets/css/styles.css`
- Google Fonts CSS from `fonts.googleapis.com`
- Google Fonts files from `fonts.gstatic.com`

That means the CSP should be strict by default but still allow the current font dependencies.

### Recommended CSP for current runtime

```php
function setSecurityHeaders(): void
{
    $csp = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "script-src 'self'",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
        "font-src 'self' https://fonts.gstatic.com data:",
        "img-src 'self' data: https:",
        "connect-src 'self'",
        "frame-src 'none'",
        "manifest-src 'self'",
        "upgrade-insecure-requests",
    ]);

    header('Content-Security-Policy: ' . $csp);
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

    if (defined('APP_ENV') && APP_ENV === 'production') {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }

    header_remove('X-Powered-By');
}
```

### Why this fits the current app

- `script-src 'self'` works because the app now loads local JS instead of CDN runtime scripts
- `style-src` still allows Google Fonts CSS and inline style attributes/classes used by templates
- `font-src` allows the current Google-hosted font files
- `connect-src 'self'` keeps API and fetch traffic same-origin by default
- `frame-ancestors 'none'` blocks clickjacking

### Hardening direction

Move toward this over time:

- remove unnecessary inline styles so `style-src 'unsafe-inline'` can be reduced or removed
- self-host fonts if you want to remove Google font dependencies from CSP entirely
- add report-only CSP first in production if rollout risk is high

---

## 8. Cookie Strategy for This App Runtime

The current bootstrap already uses:

- custom session name via `SESSION_NAME`
- `cookie_httponly = true`
- `cookie_secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'`
- `cookie_samesite = 'Lax'`
- `gc_maxlifetime = SESSION_LIFETIME`

That is a sensible local-development baseline for this app.

### Recommended session cookie strategy

For browser pages in this app:

- keep `HttpOnly` enabled
- keep `SameSite=Lax` for standard form and navigation flows
- set `Secure=true` automatically in HTTPS production
- regenerate session ID on login and privilege change
- use a dedicated cookie name like the current `aep_session`

### Runtime-specific notes

#### Local Laragon and built-in server

These run on plain HTTP during development.

- `Secure` cookies will not be sent on plain HTTP
- that means dev should keep environment logic that enables `Secure` only when HTTPS is actually active
- `SameSite=Lax` is appropriate for the current server-rendered route style

#### Production HTTPS deployment

Use stricter cookie settings:

```php
session_start([
    'cookie_httponly' => true,
    'cookie_secure'   => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
    'gc_maxlifetime'  => SESSION_LIFETIME,
]);
```

### Refresh token cookie guidance

If refresh tokens are stored in cookies for browser clients:

- use a separate cookie from the session cookie
- set `HttpOnly`
- set `Secure` in HTTPS environments
- prefer `SameSite=Strict` or `Lax` depending on your refresh flow
- scope the cookie `path` narrowly, for example `/api/auth/refresh`

Example:

```php
setcookie('aep_refresh', $refreshToken, [
    'expires'  => time() + 60 * 60 * 24 * 30,
    'path'     => '/api/auth/refresh',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict',
]);
```

Do not store refresh tokens in JavaScript-readable cookies unless the threat model explicitly accepts the XSS risk.

---

## 9. Session Security

```php
// Secure session configuration
session_start([
    'cookie_httponly' => true,          // Prevent JS access
    'cookie_secure'   => isset($_SERVER['HTTPS']), // HTTPS only
    'cookie_samesite' => 'Lax',         // CSRF protection
    'gc_maxlifetime'  => 7200,          // 2 hour lifetime
    'use_strict_mode' => true,          // Reject uninitialized session IDs
]);

// Prevent session fixation — regenerate after login/privilege change
session_regenerate_id(true);

// Session timeout
function checkSessionTimeout(int $maxAge = 7200): bool
{
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return true;
    }
    if ((time() - $_SESSION['last_activity']) > $maxAge) {
        session_unset();
        session_destroy();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

// Bind session to user agent + IP (optional, can cause issues with mobile/proxies)
function validateSessionFingerprint(): bool
{
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . ($_SERVER['REMOTE_ADDR'] ?? ''));
    if (!isset($_SESSION['fingerprint'])) {
        $_SESSION['fingerprint'] = $fingerprint;
        return true;
    }
    return hash_equals($_SESSION['fingerprint'], $fingerprint);
}
```

---

## 10. File Upload Security

```php
// NEVER trust: $_FILES['file']['type'] — it's user-controlled
// ALWAYS verify MIME type from actual file content

function validateUploadedFile(array $file, array $allowedMimes, int $maxBytes): void
{
    // Check PHP upload errors first
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new \InvalidArgumentException(match($file['error']) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large',
            UPLOAD_ERR_NO_FILE  => 'No file uploaded',
            UPLOAD_ERR_PARTIAL  => 'Partial upload',
            default => 'Upload error',
        });
    }

    // Verify it's an actual uploaded file (not a local file path trick)
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new \RuntimeException('Security violation: not an uploaded file');
    }

    // Check file size
    if ($file['size'] > $maxBytes) {
        throw new \InvalidArgumentException('File exceeds size limit');
    }

    // Check MIME from file content (not from browser claim)
    $finfo    = new \finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new \InvalidArgumentException("File type not allowed: $mimeType");
    }

    // For images — verify it can be decoded as an image (additional check)
    if (str_starts_with($mimeType, 'image/')) {
        $imageInfo = getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Invalid image file');
        }
    }
}

// Safe filename generation — never use original filename
function generateSafeFilename(string $mimeType): string
{
    $extensions = [
        'image/jpeg'      => 'jpg',
        'image/png'       => 'png',
        'image/webp'      => 'webp',
        'image/gif'       => 'gif',
        'application/pdf' => 'pdf',
    ];
    $ext = $extensions[$mimeType] ?? 'bin';
    return bin2hex(random_bytes(16)) . '.' . $ext;
}

// Upload storage — outside web root or with .htaccess protection
// ✅ Store in /storage/uploads (not accessible via HTTP)
// ✅ Serve via PHP: readfile(), with auth check
// ❌ Never store uploads in /public/ without protection
```

---

## 11. Rate Limiting

File-based rate limiting (no Redis needed):

```php
<?php declare(strict_types=1);

class RateLimiter
{
    private string $storageDir;

    public function __construct(string $storageDir = BASE_PATH . '/storage/rate-limits')
    {
        $this->storageDir = $storageDir;
        if (!is_dir($storageDir)) mkdir($storageDir, 0755, true);
    }

    public function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        $file = $this->storageDir . '/' . hash('sha256', $key) . '.json';
        $now  = time();

        $data = is_file($file) ? json_decode(file_get_contents($file), true) : ['attempts' => [], 'blocked_until' => 0];

        // Check block
        if ($data['blocked_until'] > $now) return false;

        // Remove expired attempts
        $data['attempts'] = array_filter($data['attempts'], fn($ts) => $ts > ($now - $decaySeconds));

        if (count($data['attempts']) >= $maxAttempts) {
            $data['blocked_until'] = $now + $decaySeconds;
            file_put_contents($file, json_encode($data));
            return false;
        }

        $data['attempts'][] = $now;
        file_put_contents($file, json_encode($data));
        return true;
    }

    public function clear(string $key): void
    {
        $file = $this->storageDir . '/' . hash('sha256', $key) . '.json';
        if (is_file($file)) unlink($file);
    }

    public function remainingAttempts(string $key, int $maxAttempts, int $decaySeconds): int
    {
        $file = $this->storageDir . '/' . hash('sha256', $key) . '.json';
        if (!is_file($file)) return $maxAttempts;

        $data = json_decode(file_get_contents($file), true);
        $data['attempts'] = array_filter($data['attempts'], fn($ts) => $ts > (time() - $decaySeconds));

        return max(0, $maxAttempts - count($data['attempts']));
    }
}

// Usage in login controller
$limiter = new RateLimiter();
$key = 'login:' . $request->ip();

if (!$limiter->attempt($key, maxAttempts: 5, decaySeconds: 60)) {
    http_response_code(429);
    Response::view('errors/rate-limited', ['retryAfter' => 60], 429);
}

if ($authService->attempt($email, $password)) {
    $limiter->clear($key);  // Reset on successful login
    Response::redirect('/dashboard');
}
```

---

## 12. Security Checklist

Before every deployment:

### PHP & Configuration

- [ ] `display_errors = Off` in `php.ini` (production)
- [ ] `log_errors = On` with a writable log path
- [ ] `expose_php = Off` in `php.ini`
- [ ] `session.cookie_httponly = 1`
- [ ] `session.cookie_secure = 1` (HTTPS)
- [ ] `session.use_strict_mode = 1`
- [ ] File permissions: `storage/` writable (755/644), PHP files not writable by web server
- [ ] CSP matches the app's real asset origins (`self`, Google Fonts if still used)

### Application

- [ ] All user input validated before use
- [ ] All output escaped with `htmlspecialchars()` / `e()`
- [ ] All DB queries use prepared statements
- [ ] CSRF tokens on every state-changing form
- [ ] Passwords hashed with `password_hash()` using Argon2id unless a compatibility constraint prevents it
- [ ] `session_regenerate_id(true)` called on login
- [ ] File uploads validated by MIME type (not extension)
- [ ] Uploaded files stored outside web root or protected by `.htaccess`
- [ ] Rate limiting on login, registration, password reset
- [ ] Error messages don't reveal system details to users
- [ ] Cookie settings match runtime: HTTP dev, HTTPS production, `SameSite=Lax` for web session flows

### HTTP Headers

- [ ] `X-Frame-Options: DENY` set
- [ ] `X-Content-Type-Options: nosniff` set
- [ ] `Content-Security-Policy` configured
- [ ] `Strict-Transport-Security` set (production)
- [ ] `X-Powered-By` header removed

### Configuration & Secrets

- [ ] `.env` not in web root and not committed to git
- [ ] Database user has minimum required permissions (no DROP/CREATE in production)
- [ ] Debug mode disabled in production (`APP_DEBUG=false`)
- [ ] Error logs reviewed regularly
