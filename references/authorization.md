# Authorization Reference — Roles, Permissions, Policies, Middleware, JWT Claims

## Table of Contents

1. [Scope and Goal](#1-scope-and-goal)
2. [Recommended Authorization Model](#2-recommended-authorization-model)
3. [RBAC Data Model](#3-rbac-data-model)
4. [Permission Evaluation Flow](#4-permission-evaluation-flow)
5. [Route and Controller Guards](#5-route-and-controller-guards)
6. [Policy and Ownership Checks](#6-policy-and-ownership-checks)
7. [Authorization with JWT Claims](#7-authorization-with-jwt-claims)
8. [Admin and Back-Office Rules](#8-admin-and-back-office-rules)
9. [Audit and Revocation Considerations](#9-audit-and-revocation-considerations)
10. [Implementation Checklist](#10-implementation-checklist)

---

## 1. Scope and Goal

Authorization answers a different question than authentication.

- Authentication: who is this user?
- Authorization: what is this user allowed to do here?

Do not merge them mentally or structurally. A valid login does not imply permission.

---

## 2. Recommended Authorization Model

For this stack, default to role-based access control with permission checks and policy rules.

Use:

- roles for broad capability grouping
- permissions for explicit actions
- policies for resource-level decisions like ownership or department scope

Typical examples:

- role: `admin`
- permission: `employees.view`
- permission: `employees.update`
- policy: user may edit only records they own unless they also have `employees.manage_all`

Avoid hard-coding authorization decisions directly inside views.

---

## 3. RBAC Data Model

Typical tables:

```sql
CREATE TABLE roles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_key VARCHAR(100) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    permission_key VARCHAR(150) NOT NULL UNIQUE,
    title VARCHAR(150) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE role_permissions (
    role_id BIGINT UNSIGNED NOT NULL,
    permission_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_role_permissions_role FOREIGN KEY (role_id) REFERENCES roles(id),
    CONSTRAINT fk_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES permissions(id)
);

CREATE TABLE user_roles (
    user_id BIGINT UNSIGNED NOT NULL,
    role_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, role_id),
    CONSTRAINT fk_user_roles_user FOREIGN KEY (user_id) REFERENCES users(id),
    CONSTRAINT fk_user_roles_role FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

If the app is small, one role per user may be enough. If the app is administrative, keep many-to-many support from the start.

---

## 4. Permission Evaluation Flow

Use a predictable evaluation order:

1. Confirm the user is authenticated
2. Load role keys and permission keys for that user
3. Check any global permission requirement
4. If resource-specific, run policy logic with the resource context
5. Deny by default when data is missing or ambiguous

### Permission resolver example

```php
<?php declare(strict_types=1);

function userPermissions(PDO $pdo, int $userId): array
{
    $stmt = $pdo->prepare(
        'SELECT DISTINCT p.permission_key
         FROM permissions p
         INNER JOIN role_permissions rp ON rp.permission_id = p.id
         INNER JOIN user_roles ur ON ur.role_id = rp.role_id
         WHERE ur.user_id = :user_id'
    );
    $stmt->execute(['user_id' => $userId]);

    return array_map(
        static fn(array $row): string => (string) $row['permission_key'],
        $stmt->fetchAll(PDO::FETCH_ASSOC)
    );
}

function userCan(array $permissionKeys, string $requiredPermission): bool
{
    return in_array($requiredPermission, $permissionKeys, true);
}
```

Cache permission lists per request. Do not query the database repeatedly inside one page render.

---

## 5. Route and Controller Guards

Use middleware or guard helpers for coarse authorization.

### Permission middleware pattern

```php
<?php declare(strict_types=1);

class PermissionMiddleware
{
    public function __construct(private string $requiredPermission) {}

    public function handle(array $authUser, array $permissionKeys): void
    {
        if (!userCan($permissionKeys, $this->requiredPermission)) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
```

### Controller usage

```php
<?php declare(strict_types=1);

function updateEmployee(PDO $pdo, int $employeeId, array $authUser, array $permissionKeys): void
{
    if (!userCan($permissionKeys, 'employees.update')) {
        http_response_code(403);
        exit('Forbidden');
    }

    // proceed with validated update flow
}
```

Do not rely on hidden buttons or sidebar visibility as authorization. UI is not a guard.

---

## 6. Policy and Ownership Checks

Permissions alone are often not enough.

Examples that require policy checks:

- a user may edit their own profile but not another user's
- a department manager may view only employees in their department
- an accounting user may review finance records but may not delete them

### Policy function example

```php
<?php declare(strict_types=1);

function canEditProfile(array $authUser, int $targetUserId, array $permissionKeys): bool
{
    if (userCan($permissionKeys, 'users.manage')) {
        return true;
    }

    return (int) $authUser['user_id'] === $targetUserId;
}
```

When policy and permission disagree, default to deny until the rule is made explicit.

---

## 7. Authorization with JWT Claims

JWT access tokens may carry role or scope hints, but they should not become the only source of truth for sensitive authorization unless the architecture truly requires stateless enforcement.

### Safe approach

- include minimal role or scope claims in the access token
- validate token signature and time claims
- for high-risk actions, confirm current permissions server-side

### Example JWT claims

```json
{
  "sub": "42",
  "roles": ["admin"],
  "scope": ["employees.read", "employees.update"],
  "exp": 1760000000
}
```

### Guard example

```php
<?php declare(strict_types=1);

function tokenCan(object $claims, string $requiredScope): bool
{
    $scopes = isset($claims->scope) && is_array($claims->scope) ? $claims->scope : [];
    return in_array($requiredScope, $scopes, true);
}
```

Be careful with stale JWT claims:

- a user's permissions may change before token expiry
- revoked roles do not disappear from already-issued JWTs
- short access token TTL is what limits this risk

If permissions change frequently, verify critical permissions against the database on protected actions.

---

## 8. Admin and Back-Office Rules

Administrative systems usually need stricter rules than public pages.

Recommended patterns:

- separate `admin.access` from regular business permissions
- require explicit permission for destructive actions like delete, revoke, export, impersonate
- separate read and write permissions
- separate finance, HR, and system operations where possible
- use least privilege as the default role design

Example permission set:

- `dashboard.view`
- `employees.view`
- `employees.update`
- `employees.delete`
- `finance.view`
- `finance.reconcile`
- `roles.manage`
- `audit_logs.view`

Do not create a single overloaded `admin` check and stop there. It becomes unmaintainable quickly.

---

## 9. Audit and Revocation Considerations

Authorization is not complete without traceability.

Log at least:

- login and logout
- failed access attempts
- role assignment changes
- permission changes
- token revocation events
- sensitive exports and destructive actions

If a user loses a critical role:

- revoke active refresh tokens
- invalidate server-side sessions where appropriate
- ensure future JWT access tokens reflect the new permission set

For sensitive actions, consider recording:

- actor user ID
- target resource ID
- action key
- IP address
- user agent
- request timestamp

---

## 10. Implementation Checklist

- [ ] Keep authentication and authorization separate in code and documentation
- [ ] Use roles plus permissions, not role-name checks scattered everywhere
- [ ] Deny by default when permission data is unavailable
- [ ] Add middleware for coarse route protection
- [ ] Add policy checks for ownership and scoped resources
- [ ] Treat UI hiding as presentation only, never as enforcement
- [ ] Keep JWT scopes minimal and short-lived
- [ ] Re-check critical permissions server-side for sensitive actions
- [ ] Audit role, permission, and access-denied events
- [ ] Design admin capabilities with least privilege, not convenience
