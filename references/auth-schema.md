# Auth Schema Reference — Migration-Ready Tables, Indexes, Constraints

## Table of Contents

1. [Scope](#1-scope)
2. [Design Goals](#2-design-goals)
3. [Core Authentication Tables](#3-core-authentication-tables)
4. [Authorization Tables](#4-authorization-tables)
5. [Optional Support Tables](#5-optional-support-tables)
6. [Recommended Indexes](#6-recommended-indexes)
7. [Example Migration Order](#7-example-migration-order)
8. [Seed Data Guidance](#8-seed-data-guidance)
9. [Operational Notes](#9-operational-notes)
10. [Implementation Checklist](#10-implementation-checklist)

---

## 1. Scope

This reference exists for schema-first auth scaffolding in native PHP MVC apps.

It focuses on migration-ready MySQL structures for:

- session-based authentication
- JWT access token support
- opaque refresh token rotation
- RBAC authorization
- auditability and revocation

Use it alongside:

- `references/authentication.md`
- `references/authorization.md`
- `references/pdo-mysql.md`

---

## 2. Design Goals

The schema should support these behaviors cleanly:

- one source of truth for users
- explicit role and permission relationships
- hashed refresh token storage
- revocable sessions and token families
- audit-friendly timestamps and metadata
- indexes that match real auth lookups

Do not optimize auth schema around theoretical complexity. Optimize it around the actual login, authorize, refresh, revoke, and audit paths.

---

## 3. Core Authentication Tables

### Users

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    display_name VARCHAR(150) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    email_verified_at DATETIME NULL,
    last_login_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_is_active (is_active),
    KEY idx_users_deleted_at (deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Notes:

- `password_hash` should hold Argon2id output comfortably
- keep `email` unique
- `deleted_at` supports soft delete when historical reporting matters

### User Sessions

Use this when you want server-side session auditability and forced logout control.

```sql
CREATE TABLE user_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    session_id_hash CHAR(64) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    last_seen_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_sessions_session_id_hash (session_id_hash),
    KEY idx_user_sessions_user_id (user_id),
    KEY idx_user_sessions_expires_at (expires_at),
    KEY idx_user_sessions_revoked_at (revoked_at),
    CONSTRAINT fk_user_sessions_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

Store a hash of the PHP session ID, not the raw session identifier.

### Refresh Tokens

```sql
CREATE TABLE refresh_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    family_id CHAR(36) NOT NULL,
    replaced_by_token_id BIGINT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    expires_at DATETIME NOT NULL,
    rotated_at DATETIME NULL,
    revoked_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_refresh_tokens_token_hash (token_hash),
    KEY idx_refresh_tokens_user_id (user_id),
    KEY idx_refresh_tokens_family_id (family_id),
    KEY idx_refresh_tokens_expires_at (expires_at),
    KEY idx_refresh_tokens_revoked_at (revoked_at),
    KEY idx_refresh_tokens_replaced_by_token_id (replaced_by_token_id),
    CONSTRAINT fk_refresh_tokens_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_refresh_tokens_replaced_by FOREIGN KEY (replaced_by_token_id) REFERENCES refresh_tokens(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

`family_id` helps revoke a whole token chain when replay is detected.

### Password Resets

```sql
CREATE TABLE password_resets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_password_resets_token_hash (token_hash),
    KEY idx_password_resets_user_id (user_id),
    KEY idx_password_resets_expires_at (expires_at),
    CONSTRAINT fk_password_resets_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Email Verification Tokens

```sql
CREATE TABLE email_verifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_email_verifications_token_hash (token_hash),
    KEY idx_email_verifications_user_id (user_id),
    KEY idx_email_verifications_expires_at (expires_at),
    CONSTRAINT fk_email_verifications_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 4. Authorization Tables

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_role_key (role_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(150) NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_permissions_permission_key (permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    KEY idx_role_permissions_permission_id (permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    KEY idx_user_roles_role_id (role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

These four tables are the practical baseline for RBAC in this stack.

---

## 5. Optional Support Tables

### Login Attempts

```sql
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    user_agent VARCHAR(255) NULL,
    was_successful TINYINT(1) NOT NULL,
    attempted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_attempts_ip_address (ip_address),
    KEY idx_login_attempts_email (email),
    KEY idx_login_attempts_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Audit Logs

```sql
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_user_id BIGINT UNSIGNED NULL,
    action_key VARCHAR(150) NOT NULL,
    target_type VARCHAR(100) NULL,
    target_id VARCHAR(100) NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(255) NULL,
    context_json JSON NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_logs_actor_user_id (actor_user_id),
    KEY idx_audit_logs_action_key (action_key),
    KEY idx_audit_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## 6. Recommended Indexes

These are the lookups that matter most:

- users by email
- sessions by session hash and expiry
- refresh tokens by token hash, family ID, revocation status, expiry
- permissions by permission key
- joins from user to roles to permissions
- audit log lookups by actor and timestamp

If an index does not support one of those paths, be suspicious about whether it should exist.

---

## 7. Example Migration Order

Use this order to avoid foreign key failures:

1. `users`
2. `roles`
3. `permissions`
4. `user_roles`
5. `role_permissions`
6. `user_sessions`
7. `refresh_tokens`
8. `password_resets`
9. `email_verifications`
10. `login_attempts`
11. `audit_logs`

Reverse the order for teardown migrations.

---

## 8. Seed Data Guidance

Typical initial seeds:

- roles: `admin`, `manager`, `staff`
- permissions: `dashboard.view`, `users.view`, `users.manage`, `roles.manage`, `audit_logs.view`
- one bootstrap admin user with Argon2id password hash

Do not seed production secrets or reusable plaintext passwords.

---

## 9. Operational Notes

- prefer `utf8mb4_unicode_ci` or a consistent project-wide collation
- archive or prune expired sessions, resets, and refresh tokens
- revoke entire refresh token families when replay is detected
- hash all bearer-style secrets before storage where practical
- if soft deletes are enabled for `users`, ensure auth queries exclude deleted rows

For this project, these tables fit naturally with the existing `users`, `user_roles`, `permissions`, `role_permissions`, `user_sessions`, and `audit_logs` direction already present in `sql/schema.sql`.

---

## 10. Implementation Checklist

- [ ] Keep `users.email` unique
- [ ] Store password hashes sized for Argon2id output
- [ ] Store hashed refresh tokens, not raw values
- [ ] Index all token expiry fields used in cleanup jobs
- [ ] Add foreign keys where lifecycle coupling matters
- [ ] Add audit timestamps by default
- [ ] Support token family revocation for replay response
- [ ] Exclude soft-deleted or inactive users from auth queries
- [ ] Seed one bootstrap admin path safely
- [ ] Verify indexes against actual login, refresh, and permission queries
