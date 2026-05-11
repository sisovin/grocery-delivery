# PHP 8.5 Core Reference — Language Features, CLI, OOP, Types

## Table of Contents
1. [File Header — Always Required](#1-file-header)
2. [Types & Declarations](#2-types--declarations)
3. [Enums](#3-enums)
4. [Readonly Classes & Properties](#4-readonly-classes--properties)
5. [Match Expressions](#5-match-expressions)
6. [Named Arguments](#6-named-arguments)
7. [Fibers](#7-fibers)
8. [First-Class Callables](#8-first-class-callables)
9. [Attributes](#9-attributes)
10. [Modern OOP Patterns](#10-modern-oop-patterns)
11. [Error Handling](#11-error-handling)
12. [CLI Scripts](#12-cli-scripts)
13. [Autoloading with Composer](#13-autoloading-with-composer)

---

## 1. File Header

**Every single PHP file starts with this — no exceptions:**

```php
<?php

declare(strict_types=1);

namespace App\Controllers;  // Adjust to actual namespace
```

`strict_types=1` means: type coercion is disabled. Passing a string `"1"` where `int` is expected throws a `TypeError`. This catches bugs early.

---

## 2. Types & Declarations

### Property types
```php
<?php declare(strict_types=1);

class User
{
    // Typed properties — always declare types
    public int $id;
    public string $name;
    public string $email;
    public ?string $avatarUrl = null;     // Nullable
    public bool $isActive = true;
    public UserRole $role = UserRole::User; // Enum type
    public readonly string $passwordHash;   // Readonly — set once
    public DateTimeImmutable $createdAt;

    // Union types
    public int|string $externalId;

    // Intersection types (PHP 8.1+)
    public Countable&Iterator $collection;
}
```

### Function signatures — always fully typed
```php
function createUser(
    string $name,
    string $email,
    UserRole $role = UserRole::User,
    ?string $avatarUrl = null,
): User {
    // ...
}

// Return never — for functions that always throw or exit
function abort(int $statusCode, string $message): never
{
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
}

// Nullable return
function findUserById(int $id): ?User
{
    // Returns User or null
}
```

### PHP 8.5 new features
```php
// Property hooks (PHP 8.4+, confirmed in 8.5)
class Temperature
{
    public float $celsius {
        set(float $value) {
            if ($value < -273.15) throw new \ValueError('Below absolute zero');
            $this->celsius = $value;
        }
    }

    public float $fahrenheit {
        get => $this->celsius * 9/5 + 32;
    }
}

// Asymmetric visibility (PHP 8.4+)
class Post
{
    public private(set) int $viewCount = 0;  // Public read, private write

    public function incrementViews(): void
    {
        $this->viewCount++;  // Allowed — same class
    }
}

// array_find() and array_find_key() (PHP 8.4+)
$firstAdmin = array_find($users, fn(User $u) => $u->role === UserRole::Admin);
```

---

## 3. Enums

Replace string/int constants with enums — safer, more expressive.

```php
<?php declare(strict_types=1);

namespace App\Enums;

// Pure enum (no backing value)
enum Status
{
    case Active;
    case Inactive;
    case Pending;
    case Banned;

    public function label(): string
    {
        return match($this) {
            Status::Active   => 'Active',
            Status::Inactive => 'Inactive',
            Status::Pending  => 'Pending Approval',
            Status::Banned   => 'Banned',
        };
    }

    public function isAllowedToLogin(): bool
    {
        return match($this) {
            Status::Active  => true,
            default         => false,
        };
    }
}

// Backed enum (stored in DB as string or int)
enum UserRole: string
{
    case Admin     = 'admin';
    case Editor    = 'editor';
    case User      = 'user';
    case Guest     = 'guest';

    // From DB value
    public static function fromDb(string $value): self
    {
        return self::from($value);  // Throws ValueError if invalid
    }

    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    private function permissions(): array
    {
        return match($this) {
            UserRole::Admin  => ['posts.create', 'posts.edit', 'posts.delete', 'users.manage'],
            UserRole::Editor => ['posts.create', 'posts.edit'],
            UserRole::User   => ['posts.create'],
            UserRole::Guest  => [],
        };
    }
}

// Backed enum implements interface
enum HttpMethod: string implements \Stringable
{
    case GET    = 'GET';
    case POST   = 'POST';
    case PUT    = 'PUT';
    case PATCH  = 'PATCH';
    case DELETE = 'DELETE';

    public function __toString(): string { return $this->value; }
    public function isSafe(): bool { return $this === self::GET; }
}

// Using enums in DB column:
// CREATE TABLE users (role ENUM('admin','editor','user','guest') NOT NULL DEFAULT 'user');
// In PHP: UserRole::from($row['role'])
```

---

## 4. Readonly Classes & Properties

Use for DTOs (Data Transfer Objects), value objects, config — anything that shouldn't mutate.

```php
<?php declare(strict_types=1);

namespace App\DTOs;

// Readonly class — ALL properties are implicitly readonly
readonly class CreateUserDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role = UserRole::User,
    ) {}

    // Can have methods
    public function toArray(): array
    {
        return [
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role->value,
        ];
    }
}

// Value object
readonly class Money
{
    public function __construct(
        public int $amount,      // Store in cents
        public string $currency,
    ) {}

    public function add(Money $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Currency mismatch');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function format(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . $this->currency;
    }
}

// Usage
$dto = new CreateUserDTO(
    name: 'Alice',
    email: 'alice@example.com',
    password: 'SecurePass123!'
);
// $dto->name = 'Bob';  ← Fatal error — readonly
```

---

## 5. Match Expressions

Replace switch statements — exhaustive, expression-based, no fall-through.

```php
// Basic match — returns a value
$statusCode = 404;
$message = match($statusCode) {
    200, 201 => 'Success',
    400      => 'Bad Request',
    401      => 'Unauthorized',
    403      => 'Forbidden',
    404      => 'Not Found',
    500      => 'Server Error',
    default  => 'Unknown Status',
};

// Match with no-match exception
function getContentType(string $extension): string
{
    return match($extension) {
        'json'       => 'application/json',
        'xml'        => 'application/xml',
        'html', 'htm'=> 'text/html',
        'css'        => 'text/css',
        'js'         => 'application/javascript',
        'png'        => 'image/png',
        'jpg', 'jpeg'=> 'image/jpeg',
        default      => throw new \InvalidArgumentException("Unknown type: $extension"),
    };
}

// Match with complex conditions
$price = 150;
$discount = match(true) {
    $price >= 500 => 0.20,
    $price >= 200 => 0.10,
    $price >= 100 => 0.05,
    default       => 0.00,
};
```

---

## 6. Named Arguments

Essential for built-ins with confusing parameter order, and for self-documenting code.

```php
// Built-in functions — named args eliminate param order confusion
$result = array_slice(
    array: $items,
    offset: 0,
    length: 10,
    preserve_keys: true,
);

$rounded = round(num: 3.14159, precision: 2, mode: PHP_ROUND_HALF_UP);

str_contains(haystack: $email, needle: '@');

// Skip optional params to set only the ones you need
htmlspecialchars(string: $input, encoding: 'UTF-8');

// Constructor promotion with named args
class DatabaseConfig
{
    public function __construct(
        public readonly string $host = 'localhost',
        public readonly int $port = 3306,
        public readonly string $charset = 'utf8mb4',
        public readonly int $timeout = 5,
    ) {}
}

$config = new DatabaseConfig(host: 'db.example.com', timeout: 10);
// port and charset use defaults
```

---

## 7. Fibers

Cooperative concurrency within a single thread — useful in CLI scripts.

```php
<?php declare(strict_types=1);

// Fiber — suspendable function
$fiber = new Fiber(function (): void {
    $value = Fiber::suspend('first suspend');
    echo "Fiber received: $value\n";

    Fiber::suspend('second suspend');
    echo "Fiber finishing\n";
});

$result1 = $fiber->start();         // Returns 'first suspend'
$result2 = $fiber->resume('hello'); // Returns 'second suspend'
$fiber->resume();                   // Fiber finishes

// Practical: processing large datasets without blocking
function processBatch(array $ids, callable $processor): \Generator
{
    $fiber = new Fiber(function () use ($ids, $processor): void {
        foreach ($ids as $id) {
            $processor($id);
            Fiber::suspend($id);  // Yield control after each item
        }
    });

    $fiber->start();
    while (!$fiber->isTerminated()) {
        yield $fiber->getReturn() ?? $fiber->resume();
    }
}
```

---

## 8. First-Class Callables

```php
// Old way
$lengths = array_map(fn($s) => strlen($s), $strings);

// First-class callable syntax
$lengths = array_map(strlen(...), $strings);

// Works with any callable
$trimmed  = array_map(trim(...), $inputs);
$filtered = array_filter($emails, str_contains(...));  // Only works as 2-arg partial

// With methods
$model = new UserModel($db);
$users = array_map($model->find(...), $ids);

// Storing callables
$validators = [
    'email'    => filter_var(...),
    'trim'     => trim(...),
    'sanitize' => htmlspecialchars(...),
];
```

---

## 9. Attributes

```php
<?php declare(strict_types=1);

// Define custom attribute
#[\Attribute(\Attribute::TARGET_METHOD)]
class Route
{
    public function __construct(
        public readonly string $method,
        public readonly string $path,
    ) {}
}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Validate
{
    public function __construct(
        public readonly string $rule,
        public readonly string $message = '',
    ) {}
}

// Use attributes on methods/properties
class UserController
{
    #[Route('GET', '/users')]
    public function index(): void { /* ... */ }

    #[Route('POST', '/users')]
    public function store(): void { /* ... */ }

    #[Route('DELETE', '/users/{id}')]
    public function destroy(int $id): void { /* ... */ }
}

// Read attributes via reflection
function registerRoutes(object $controller): array
{
    $routes = [];
    $reflection = new \ReflectionClass($controller);

    foreach ($reflection->getMethods() as $method) {
        $attrs = $method->getAttributes(Route::class);
        foreach ($attrs as $attr) {
            $route = $attr->newInstance();
            $routes[] = [
                'method'     => $route->method,
                'path'       => $route->path,
                'controller' => [$controller, $method->getName()],
            ];
        }
    }
    return $routes;
}
```

---

## 10. Modern OOP Patterns

### Constructor promotion
```php
class Product
{
    public function __construct(
        private readonly int $id,
        private string $name,
        private float $price,
        private int $stock = 0,
        private ?string $imageUrl = null,
    ) {}

    public function getName(): string { return $this->name; }
    public function getPrice(): float { return $this->price; }
    public function isInStock(): bool { return $this->stock > 0; }
}
```

### Interface + abstract class pattern
```php
interface Repository
{
    public function findById(int $id): ?object;
    public function findAll(array $filters = [], int $limit = 20, int $offset = 0): array;
    public function save(object $entity): object;
    public function delete(int $id): bool;
}

abstract class BaseRepository implements Repository
{
    public function __construct(protected \PDO $db) {}

    protected function execute(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}

class UserRepository extends BaseRepository
{
    public function findById(int $id): ?User
    {
        $stmt = $this->execute('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]);
        $row = $stmt->fetch();
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): User
    {
        return new User(
            id: $row['id'],
            name: $row['name'],
            email: $row['email'],
            role: UserRole::from($row['role']),
        );
    }
    // ... implement other methods
}
```

---

## 11. Error Handling

```php
// Custom exception hierarchy
class AppException extends \RuntimeException {}
class NotFoundException extends AppException
{
    public function __construct(string $resource, int $id)
    {
        parent::__construct("$resource with ID $id not found", 404);
    }
}
class ValidationException extends AppException
{
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Validation failed', 422);
    }
    public function getErrors(): array { return $this->errors; }
}
class UnauthorizedException extends AppException
{
    public function __construct(string $message = 'Unauthorized')
    {
        parent::__construct($message, 401);
    }
}

// Global error handler — register in bootstrap
function registerErrorHandlers(): void
{
    set_exception_handler(function (\Throwable $e): void {
        $isDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

        error_log(sprintf('[%s] %s in %s:%d', get_class($e), $e->getMessage(), $e->getFile(), $e->getLine()));

        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "Error: {$e->getMessage()}\n");
            exit(1);
        }

        $code = $e->getCode() ?: 500;
        http_response_code(in_array($code, [400,401,403,404,422,500], true) ? $code : 500);

        if (isJsonRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'error'  => $e->getMessage(),
                'errors' => $e instanceof ValidationException ? $e->getErrors() : [],
                ...($isDebug ? ['trace' => $e->getTraceAsString()] : []),
            ]);
        } else {
            include __DIR__ . '/../views/errors/error.php';
        }
        exit;
    });

    set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
        throw new \ErrorException($message, 0, $severity, $file, $line);
    });
}
```

---

## 12. CLI Scripts

```php
#!/usr/bin/env php
<?php declare(strict_types=1);

// CLI-only guard
if (php_sapi_name() !== 'cli') {
    exit('This script must be run from the command line.');
}

require __DIR__ . '/../vendor/autoload.php';

// Parse CLI arguments cleanly
$opts = getopt('v', ['migrate', 'seed', 'rollback', 'help', 'env:'], $restIndex);

if (isset($opts['help'])) {
    echo <<<HELP
    Usage: php cli.php [options]
      --migrate       Run pending migrations
      --seed          Seed the database
      --rollback      Rollback last migration
      --env=<env>     Environment (local|staging|production)
      -v              Verbose output
    HELP;
    exit(0);
}

// Colorized output helpers
function info(string $msg): void  { echo "\033[32m[INFO]\033[0m  $msg\n"; }
function error(string $msg): void { fwrite(STDERR, "\033[31m[ERROR]\033[0m $msg\n"); }
function warn(string $msg): void  { echo "\033[33m[WARN]\033[0m  $msg\n"; }

// Progress bar
function progressBar(int $current, int $total, int $width = 40): void
{
    $percent = $total > 0 ? round($current / $total * 100) : 0;
    $filled  = (int) ($width * $current / max(1, $total));
    $bar     = str_repeat('█', $filled) . str_repeat('░', $width - $filled);
    echo "\r[$bar] $percent% ($current/$total)";
    if ($current >= $total) echo "\n";
}

// Example: interactive prompt
function prompt(string $question, bool $secret = false): string
{
    echo $question . ': ';
    if ($secret) system('stty -echo');
    $input = trim(fgets(STDIN));
    if ($secret) { system('stty echo'); echo "\n"; }
    return $input;
}

// Confirmation prompt
function confirm(string $question): bool
{
    echo "$question [y/N]: ";
    return strtolower(trim(fgets(STDIN))) === 'y';
}
```

---

## 13. Autoloading with Composer

Even without a framework, use Composer for PSR-4 autoloading.

```json
// composer.json
{
    "name": "myapp/myapp",
    "autoload": {
        "psr-4": {
            "App\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "require": {
        "php": "^8.5",
        "vlucas/phpdotenv": "^5.6"
    },
    "require-dev": {
        "phpunit/phpunit": "^11"
    }
}
```

```php
// public/index.php — bootstrap
<?php declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();
$dotenv->required(['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'APP_SECRET']);

// Run app
$app = new \App\Core\Application();
$app->run();
```