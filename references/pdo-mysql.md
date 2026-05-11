# PDO MySQL Reference — Setup, Queries, Transactions, Migrations, Patterns

## Table of Contents
1. [PDO Connection & Configuration](#1-pdo-connection--configuration)
2. [Prepared Statements — The Only Way](#2-prepared-statements)
3. [CRUD Patterns](#3-crud-patterns)
4. [Transactions](#4-transactions)
5. [Pagination](#5-pagination)
6. [Database Schema — MySQL Best Practices](#6-database-schema)
7. [Migrations (File-Based, No ORM)](#7-migrations)
8. [Query Builder Pattern](#8-query-builder-pattern)
9. [Database Seeding](#9-database-seeding)
10. [Performance & Optimization](#10-performance--optimization)

---

## 1. PDO Connection & Configuration

```php
<?php declare(strict_types=1);

namespace App\Core;

class Database
{
    private static ?self $instance = null;
    private \PDO $connection;

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $_ENV['DB_HOST'],
            $_ENV['DB_PORT'] ?? '3306',
            $_ENV['DB_NAME'],
        );

        $this->connection = new \PDO(
            dsn: $dsn,
            username: $_ENV['DB_USER'],
            password: $_ENV['DB_PASS'],
            options: [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,   // Throw on error
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,         // Arrays by default
                \PDO::ATTR_EMULATE_PREPARES   => false,                     // Real prepared stmts
                \PDO::ATTR_STRINGIFY_FETCHES  => false,                     // Keep int/float types
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                \PDO::ATTR_TIMEOUT            => 5,
            ]
        );
    }

    // Singleton — one connection per request
    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }

    // Convenience — direct PDO method forwarding
    public function prepare(string $sql): \PDOStatement
    {
        return $this->connection->prepare($sql);
    }

    public function query(string $sql): \PDOStatement
    {
        return $this->connection->query($sql);
    }

    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    // Prevent cloning & unserialization
    private function __clone() {}
    public function __wakeup(): never { throw new \RuntimeException('Cannot unserialize singleton'); }
}
```

### .env setup
```ini
# .env.example
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=myapp
DB_USER=myapp_user
DB_PASS=
DB_ROOT_PASS=

APP_ENV=local
APP_DEBUG=true
APP_SECRET=change-me-to-a-long-random-string
APP_URL=http://localhost:8000

SESSION_LIFETIME=7200
```

---

## 2. Prepared Statements

**RULE: Every query with external data uses prepared statements. No exceptions.**

```php
<?php declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class UserModel
{
    private \PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ✅ Correct — named placeholders (preferred for readability)
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, created_at FROM users WHERE email = :email AND deleted_at IS NULL'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // ✅ Correct — positional placeholders (fine for simple queries)
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, avatar_url, created_at FROM users WHERE id = ? AND deleted_at IS NULL'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    // ✅ Correct — bindValue with explicit types
    public function findActiveUsers(int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role FROM users WHERE deleted_at IS NULL ORDER BY name ASC LIMIT :limit OFFSET :offset'
        );
        // IMPORTANT: LIMIT and OFFSET MUST be bound as integers — :limit can't be a string
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ❌ NEVER DO THIS — SQL injection vulnerability
    // $stmt = $this->db->query("SELECT * FROM users WHERE email = '$email'");

    // Dynamic IN clause — safe pattern
    public function findByIds(array $ids): array
    {
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "SELECT id, name, email FROM users WHERE id IN ($placeholders)"
        );
        $stmt->execute(array_values($ids));
        return $stmt->fetchAll();
    }
}
```

---

## 3. CRUD Patterns

```php
// CREATE
public function create(array $data): int
{
    $stmt = $this->db->prepare(
        'INSERT INTO users (name, email, password_hash, role, created_at)
         VALUES (:name, :email, :password_hash, :role, NOW())'
    );
    $stmt->execute([
        'name'          => $data['name'],
        'email'         => strtolower(trim($data['email'])),
        'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
        'role'          => $data['role'] ?? 'user',
    ]);
    return (int) $this->db->lastInsertId();
}

// READ with column selection (never SELECT *)
public function findAll(array $filters = [], string $orderBy = 'created_at', string $dir = 'DESC'): array
{
    // Whitelist sort columns to prevent injection
    $allowedColumns = ['name', 'email', 'created_at', 'role'];
    $allowedDirs    = ['ASC', 'DESC'];

    $col = in_array($orderBy, $allowedColumns, true) ? $orderBy : 'created_at';
    $dir = in_array(strtoupper($dir), $allowedDirs, true) ? strtoupper($dir) : 'DESC';

    $where  = ['deleted_at IS NULL'];
    $params = [];

    if (!empty($filters['role'])) {
        $where[]        = 'role = :role';
        $params['role'] = $filters['role'];
    }
    if (!empty($filters['search'])) {
        $where[]          = '(name LIKE :search OR email LIKE :search)';
        $params['search'] = '%' . $filters['search'] . '%';
    }

    $sql  = 'SELECT id, name, email, role, created_at FROM users WHERE ' . implode(' AND ', $where);
    $sql .= " ORDER BY $col $dir";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// UPDATE
public function update(int $id, array $data): bool
{
    $allowed = ['name', 'email', 'avatar_url', 'role'];
    $fields  = array_intersect_key($data, array_flip($allowed));

    if (empty($fields)) return false;

    $set    = implode(', ', array_map(fn($k) => "$k = :$k", array_keys($fields)));
    $params = $fields;
    $params['id'] = $id;

    $stmt = $this->db->prepare("UPDATE users SET $set, updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
    $stmt->execute($params);
    return $stmt->rowCount() > 0;
}

// SOFT DELETE (preferred over hard delete)
public function delete(int $id): bool
{
    $stmt = $this->db->prepare('UPDATE users SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

// HARD DELETE (only when truly needed)
public function hardDelete(int $id): bool
{
    $stmt = $this->db->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

// EXISTS check
public function emailExists(string $email, ?int $excludeId = null): bool
{
    $sql    = 'SELECT 1 FROM users WHERE email = :email AND deleted_at IS NULL';
    $params = ['email' => strtolower($email)];

    if ($excludeId !== null) {
        $sql .= ' AND id != :exclude_id';
        $params['exclude_id'] = $excludeId;
    }

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return (bool) $stmt->fetchColumn();
}
```

---

## 4. Transactions

```php
// Simple transaction
public function transferFunds(int $fromId, int $toId, float $amount): void
{
    $this->db->beginTransaction();
    try {
        // Debit
        $stmt = $this->db->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ? AND balance >= ?');
        $stmt->execute([$amount, $fromId, $amount]);
        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Insufficient funds');
        }

        // Credit
        $stmt = $this->db->prepare('UPDATE accounts SET balance = balance + ? WHERE id = ?');
        $stmt->execute([$amount, $toId]);

        // Log the transaction
        $stmt = $this->db->prepare('INSERT INTO transactions (from_id, to_id, amount, created_at) VALUES (?, ?, ?, NOW())');
        $stmt->execute([$fromId, $toId, $amount]);

        $this->db->commit();
    } catch (\Throwable $e) {
        $this->db->rollBack();
        throw $e;  // Re-throw — let caller decide how to handle
    }
}

// Transaction helper method — use in BaseRepository
protected function transaction(callable $callback): mixed
{
    $this->db->beginTransaction();
    try {
        $result = $callback($this->db);
        $this->db->commit();
        return $result;
    } catch (\Throwable $e) {
        $this->db->rollBack();
        throw $e;
    }
}
```

---

## 5. Pagination

```php
// Offset pagination (simple, good for most apps)
public function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
{
    $page    = max(1, $page);
    $perPage = min(100, max(1, $perPage));  // Clamp to prevent abuse
    $offset  = ($page - 1) * $perPage;

    // Count query (with same WHERE conditions)
    $countStmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL');
    $countStmt->execute();
    $total = (int) $countStmt->fetchColumn();

    // Data query
    $stmt = $this->db->prepare(
        'SELECT id, name, email, role, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT :limit OFFSET :offset'
    );
    $stmt->bindValue(':limit', $perPage, \PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
    $stmt->execute();

    return [
        'data'        => $stmt->fetchAll(),
        'total'       => $total,
        'per_page'    => $perPage,
        'current_page'=> $page,
        'last_page'   => (int) ceil($total / $perPage),
        'from'        => $offset + 1,
        'to'          => min($offset + $perPage, $total),
    ];
}
```

---

## 6. Database Schema

### MySQL conventions
```sql
-- Always UTF8MB4, always InnoDB
CREATE DATABASE myapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myapp;

-- Users table
CREATE TABLE users (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    name        VARCHAR(100)    NOT NULL,
    email       VARCHAR(255)    NOT NULL,
    password_hash VARCHAR(255)  NOT NULL,
    role        ENUM('admin','editor','user') NOT NULL DEFAULT 'user',
    avatar_url  VARCHAR(500)    NULL,
    remember_token VARCHAR(100) NULL,
    email_verified_at DATETIME  NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_email (email),
    KEY idx_role (role),
    KEY idx_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table (DB-backed sessions)
CREATE TABLE sessions (
    id          VARCHAR(128)    NOT NULL,
    user_id     INT UNSIGNED    NULL,
    ip_address  VARCHAR(45)     NULL,
    user_agent  VARCHAR(500)    NULL,
    payload     LONGTEXT        NOT NULL,
    last_activity DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_user_id (user_id),
    KEY idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Posts with foreign key
CREATE TABLE posts (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    user_id     INT UNSIGNED    NOT NULL,
    title       VARCHAR(255)    NOT NULL,
    slug        VARCHAR(255)    NOT NULL,
    content     LONGTEXT        NOT NULL,
    status      ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
    published_at DATETIME       NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME        NULL ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  DATETIME        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY idx_slug (slug),
    KEY idx_user_id (user_id),
    KEY idx_status_published (status, published_at),
    CONSTRAINT fk_posts_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 7. Migrations

File-based migration system — no framework needed.

```
database/
├── migrations/
│   ├── 0001_create_users_table.sql
│   ├── 0002_create_sessions_table.sql
│   └── 0003_add_avatar_to_users.sql
└── seeds/
    └── users_seeder.php
```

```php
#!/usr/bin/env php
<?php declare(strict_types=1);

// bin/migrate.php
require __DIR__ . '/../vendor/autoload.php';

(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->load();

$db = (new App\Core\Database())->getConnection();

// Ensure migrations table exists
$db->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        migration   VARCHAR(255) NOT NULL,
        batch       INT NOT NULL,
        run_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY idx_migration (migration)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Find run migrations
$ran = $db->query("SELECT migration FROM migrations")->fetchAll(\PDO::FETCH_COLUMN);
$batch = (int) ($db->query("SELECT MAX(batch) FROM migrations")->fetchColumn() ?? 0) + 1;

// Find pending migration files
$files = glob(__DIR__ . '/../database/migrations/*.sql');
sort($files);

$pending = array_filter($files, fn($f) => !in_array(basename($f), $ran, true));

if (empty($pending)) {
    echo "Nothing to migrate.\n";
    exit(0);
}

foreach ($pending as $file) {
    $name = basename($file);
    echo "Migrating: $name ... ";

    $db->beginTransaction();
    try {
        $sql = file_get_contents($file);
        // Split on semicolons to handle multi-statement files
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $db->exec($statement);
        }
        $stmt = $db->prepare("INSERT INTO migrations (migration, batch) VALUES (?, ?)");
        $stmt->execute([$name, $batch]);
        $db->commit();
        echo "Done.\n";
    } catch (\Throwable $e) {
        $db->rollBack();
        echo "FAILED: {$e->getMessage()}\n";
        exit(1);
    }
}
```

---

## 8. Query Builder Pattern

```php
<?php declare(strict_types=1);

namespace App\Core;

class QueryBuilder
{
    private string $table = '';
    private array $selects = ['*'];
    private array $wheres = [];
    private array $params = [];
    private ?string $orderBy = null;
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(private \PDO $db) {}

    public static function table(string $table): self
    {
        $qb = new self(\App\Core\Database::getInstance()->getConnection());
        $qb->table = $table;
        return $qb;
    }

    public function select(string ...$columns): self
    {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $key = str_replace('.', '_', $column) . '_' . count($this->wheres);
        $this->wheres[] = "$column $operator :$key";
        $this->params[$key] = $value;
        return $this;
    }

    public function whereNull(string $column): self
    {
        $this->wheres[] = "$column IS NULL";
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $allowed = ['ASC', 'DESC'];
        $this->orderBy = "$column " . (in_array(strtoupper($direction), $allowed, true) ? strtoupper($direction) : 'ASC');
        return $this;
    }

    public function limit(int $limit): self  { $this->limit = $limit; return $this; }
    public function offset(int $offset): self { $this->offset = $offset; return $this; }

    public function get(): array
    {
        $stmt = $this->db->prepare($this->buildSql());
        $stmt->execute($this->params);
        return $stmt->fetchAll();
    }

    public function first(): ?array
    {
        $this->limit = 1;
        $stmt = $this->db->prepare($this->buildSql());
        $stmt->execute($this->params);
        return $stmt->fetch() ?: null;
    }

    public function count(): int
    {
        $this->selects = ['COUNT(*) as count'];
        $stmt = $this->db->prepare($this->buildSql());
        $stmt->execute($this->params);
        return (int) $stmt->fetchColumn();
    }

    private function buildSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->selects) . " FROM {$this->table}";
        if ($this->wheres) $sql .= ' WHERE ' . implode(' AND ', $this->wheres);
        if ($this->orderBy) $sql .= " ORDER BY {$this->orderBy}";
        if ($this->limit !== null)  $sql .= " LIMIT {$this->limit}";
        if ($this->offset !== null) $sql .= " OFFSET {$this->offset}";
        return $sql;
    }
}

// Usage
$users = QueryBuilder::table('users')
    ->select('id', 'name', 'email', 'role')
    ->where('role', 'admin')
    ->whereNull('deleted_at')
    ->orderBy('name')
    ->limit(20)
    ->get();
```

---

## 9. Database Seeding

```php
#!/usr/bin/env php
<?php declare(strict_types=1);
// bin/seed.php

require __DIR__ . '/../vendor/autoload.php';
(Dotenv\Dotenv::createImmutable(dirname(__DIR__)))->load();

$db = App\Core\Database::getInstance()->getConnection();

echo "Seeding users...\n";

$users = [
    ['name' => 'Admin User',  'email' => 'admin@example.com',  'role' => 'admin'],
    ['name' => 'Test Editor', 'email' => 'editor@example.com', 'role' => 'editor'],
    ['name' => 'Test User',   'email' => 'user@example.com',   'role' => 'user'],
];

$stmt = $db->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, :role)
     ON DUPLICATE KEY UPDATE name = VALUES(name)'
);

foreach ($users as $user) {
    $stmt->execute([
        'name'  => $user['name'],
        'email' => $user['email'],
        'hash'  => password_hash('password', PASSWORD_BCRYPT, ['cost' => 12]),
        'role'  => $user['role'],
    ]);
    echo "  Seeded: {$user['email']}\n";
}

echo "Done.\n";
```

---

## 10. Performance & Optimization

### Indexes — create for every lookup column
```sql
-- Compound index for common filter + sort
CREATE INDEX idx_posts_status_date ON posts (status, published_at DESC);

-- Covering index (query only hits the index, not the table)
CREATE INDEX idx_users_email_name ON users (email, name);

-- Full-text search
ALTER TABLE posts ADD FULLTEXT idx_posts_fulltext (title, content);
-- Query:
-- WHERE MATCH(title, content) AGAINST (:query IN BOOLEAN MODE)
```

### Avoid common slow patterns
```php
// ❌ N+1: one query per user's posts
foreach ($users as $user) {
    $user['posts'] = $postModel->findByUserId($user['id']);
}

// ✅ Two queries total using IN
$userIds = array_column($users, 'id');
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$stmt = $db->prepare("SELECT * FROM posts WHERE user_id IN ($placeholders)");
$stmt->execute($userIds);
$posts = $stmt->fetchAll();

// Group posts by user_id in PHP
$postsByUser = [];
foreach ($posts as $post) {
    $postsByUser[$post['user_id']][] = $post;
}

// ❌ Counting with SELECT *
$total = count($db->query("SELECT * FROM users")->fetchAll());

// ✅ COUNT at DB level
$total = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
```

### MySQL server settings (my.cnf)
```ini
[mysqld]
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci
innodb_buffer_pool_size = 256M     # Set to 50-70% of available RAM
innodb_log_file_size = 64M
max_connections = 150
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 1
```