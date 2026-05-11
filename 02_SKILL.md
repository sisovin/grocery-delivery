---
name: php-fullstack
description: |
	Practical full-stack web development skill for native PHP 8.5 applications using
	PDO + MySQL, HTML5, Tailwind CSS 4.2.2, and vanilla JavaScript. Use this skill when
	the user asks to build, scaffold, refactor, debug, review, or extend a PHP web app;
	write PHP 8.5 code; work with PDO and MySQL queries, transactions, migrations, or
	schema design; build HTML pages, forms, dashboards, or reusable components; style UI
	with Tailwind CSS v4; write vanilla JavaScript for fetch, modules, DOM updates, or
	interactivity; implement routing, MVC, REST APIs, authentication, authorization,
	sessions, Argon2id password flows, CSRF protection, JWT access tokens, refresh token
	rotation, uploads, validation, or deployment basics without a framework.
license: MIT
---

# PHP 8.5 full-stack developer skill

This skill is for building real PHP applications without hiding behind a framework.
The stack is simple on purpose:

- PHP 8.5
- PDO + MySQL
- HTML5
- Tailwind CSS 4.2.2
- Vanilla JavaScript

No fluff. No tutorial voice. Build clean systems that you can debug, extend, and ship.

## What this skill covers

Use this skill when the task involves any of the following:

- Writing or refactoring PHP 8.5 code
- Designing or fixing MVC structure, routing, controllers, services, or repositories
- Working with PDO, MySQL queries, transactions, migrations, pagination, or indexing
- Building HTML pages, forms, tables, dashboards, or reusable UI pieces
- Styling with Tailwind CSS 4.2.2 using CSS-first configuration
- Adding vanilla JavaScript with fetch, modules, progressive enhancement, or DOM updates
- Implementing auth, sessions, CSRF protection, validation, or file uploads
- Implementing authorization, JWT token flows, or refresh token rotation
- Designing REST endpoints and JSON responses
- Reviewing native PHP full-stack code for architecture, security, or maintainability

## Reference files

Read the relevant reference file before writing code. Do not load everything by default.
Pick the files that match the task.

| File | Read when |
|---|---|
| `references/php-core.md` | PHP 8.5 classes, enums, readonly objects, CLI scripts, types, error handling |
| `references/pdo-mysql.md` | PDO setup, queries, transactions, migrations, schema work, query patterns |
| `references/frontend.md` | HTML5 semantics, Tailwind CSS 4.2.2, responsive UI, forms, vanilla JS |
| `references/architecture.md` | MVC structure, routing, request lifecycle, sessions, auth, REST APIs, uploads |
| `references/security.md` | Validation, output escaping, SQL injection prevention, CSRF, password handling, headers |
| `references/authentication.md` | Login flows, Argon2id password hashing, session auth, CSRF in auth forms, JWT access tokens, refresh token rotation |
| `references/authorization.md` | RBAC, permissions, policies, route guards, admin access rules, JWT scope and role evaluation |
| `references/auth-schema.md` | Migration-ready auth tables, indexes, foreign keys, seed guidance, and schema-first scaffolding order |
| `references/middleware.md` | Concrete AuthMiddleware, PermissionMiddleware, JwtMiddleware patterns for this native PHP MVC stack |

For multi-layer work such as a dashboard, admin panel, auth flow, authorization layer, or CRUD system, read all relevant references and default to all nine when auth and access control are involved.

## Default stack assumptions

Unless the repo clearly does something else, assume these defaults:

- PHP version is 8.5
- The app uses a public web root such as `public/`
- Requests go through a front controller like `public/index.php`
- Database access goes through PDO with MySQL and `utf8mb4`
- Secrets live in `.env`, with a committed `.env.example`
- JavaScript is vanilla ES modules
- Tailwind is v4.2.2 with CSS-first config
- Composer handles autoloading even if there is no framework

## Working approach

### 1. Clarify the execution context

Before writing code, confirm the parts that affect the design:

- Single front controller or multiple PHP entry points
- Existing folder layout or greenfield structure
- Whether Tailwind is built with npm or loaded for quick prototypes
- Whether the task is server-rendered HTML, JSON API, or both
- Whether session auth, token auth, or both are already in play

### 2. Prefer simple structure over clever structure

The default architecture is:

- thin controllers
- services for business logic
- repositories or models for persistence
- views or templates for rendering
- middleware for auth, CSRF, and request guards

Controllers should not hold business rules. If they do, the codebase gets ugly fast.

### 3. Use PHP 8.5 features on purpose

Reach for modern language features when they make the code clearer:

- enums for roles, states, and action types
- readonly classes or properties for DTOs and value objects
- match expressions instead of bloated switch blocks
- named arguments when a call would be ambiguous otherwise
- first-class callables when passing behavior around
- strict typing everywhere practical

Do not use modern syntax just to show off. Use it to remove ambiguity.

### 4. Build from the edges inward

A solid implementation usually follows this order:

1. Request shape and validation
2. Domain or service logic
3. Database queries and transactions
4. Response shape or rendered view
5. Frontend behavior and feedback states
6. Security pass

## Core capabilities

### PHP foundation

Expect to handle:

- typed properties, parameters, and returns
- exceptions and custom exception types
- namespaces and PSR-4 autoloading
- pure functions versus side-effect code
- CLI scripts for migrations, seeding, or admin tasks

Every PHP file should start with `declare(strict_types=1);`.

### Backend engineering

The baseline backend skill set includes:

- routing and dispatch
- controllers, services, repositories, and DTOs
- request parsing and response generation
- REST endpoints with correct HTTP status codes
- session-based auth and role checks
- file uploads with validation and safe storage

### PDO and MySQL

The database layer should be boring in the best way:

- prepared statements only
- explicit column lists, not `SELECT *`
- transactions for multi-step writes
- pagination with safe `LIMIT` and `OFFSET`
- indexes that match real query patterns
- schemas that are normalized until there is a good reason not to be

Write queries you can explain six months later.

### Frontend

Frontend work in this stack means:

- semantic HTML5, not div soup
- mobile-first responsive layouts
- reusable components such as navbars, cards, forms, tables, and modals
- accessible labels, focus states, and keyboard behavior
- Tailwind utilities used with discipline, not as a dumping ground
- vanilla JS for fetch, stateful UI updates, and progressive enhancement

### Full-stack integration

Real app work usually comes down to this:

- validate on the server first
- return useful errors
- preserve form values when possible
- show loading and failure states in the UI
- keep API and form behavior consistent
- make simple things simple before adding reactive complexity

## Tailwind CSS 4.2.2 guidance

Use Tailwind v4 the way it was designed:

- prefer `@import "tailwindcss"` in the CSS entry point
- define project tokens in `@theme`
- use `@layer` for base and component rules
- use `@utility` for small reusable utilities
- keep responsive behavior mobile-first

For production, prefer the CLI build. For fast prototypes, a lightweight import is fine if the repo already works that way.

## Security rules

These are not optional:

1. Use prepared statements everywhere.
2. Validate input before it reaches persistence or business logic.
3. Escape output with `htmlspecialchars()` at render time.
4. Hash passwords with `password_hash()` and verify with `password_verify()`.
5. Regenerate the session ID after login.
6. Add CSRF protection to every state-changing form.
7. Validate uploads by MIME type, size, and destination path.
8. Keep secrets out of version control.
9. Do not silence errors. Log them properly.
10. Do not trust client-side validation.

If security is hand-waved away, the rest of the app does not matter.

## Non-negotiable code rules

1. Start every PHP file with `declare(strict_types=1);`.
2. Type properties, parameters, and return values unless there is a real reason not to.
3. Keep controllers thin.
4. Do not mix SQL construction with raw request data.
5. Do not put business logic in views.
6. Do not commit `.env`.
7. Prefer soft deletes and audit trails when the product needs traceability.
8. Add timestamps to tables by default.
9. Use transactions when one write depends on another.
10. Name things clearly. Future-you is part of the team.

## Decision guide

| Task | Read first |
|---|---|
| Write a PHP class, enum, DTO, service, or CLI script | `references/php-core.md` |
| Add or fix a PDO query, transaction, migration, or schema | `references/pdo-mysql.md` |
| Build an HTML page, form, layout, or dashboard | `references/frontend.md` |
| Add routing, MVC structure, auth flow, API endpoints, or uploads | `references/architecture.md` |
| Review validation, escaping, CSRF, sessions, passwords, or headers | `references/security.md` |
| Implement login, logout, password hashing, JWT, refresh tokens, or session auth | `references/authentication.md` |
| Implement roles, permissions, policies, route guards, or admin access rules | `references/authorization.md` |
| Design migration-ready auth tables, indexes, and seeding order | `references/auth-schema.md` |
| Implement request guards with concrete middleware classes | `references/middleware.md` |
| Build a CRUD feature or full module | All five references |

For auth-heavy CRUD or admin systems, treat `references/authentication.md`, `references/authorization.md`, `references/auth-schema.md`, and `references/middleware.md` as required, not optional.

## Common project types this skill should support

You should be able to build and maintain projects like these:

- admin dashboards
- CRM systems
- content management tools
- internal tools
- classic CRUD apps
- server-rendered apps with a small JSON API layer

Typical tables you will need sooner rather than later:

- users
- roles
- permissions
- sessions
- uploads
- logs or audit_logs

Typical relationships you need to handle well:

- one-to-many
- many-to-many
- soft-deleted records that still matter for reporting

## Performance and operations

Be practical:

- add indexes when the query pattern justifies them
- avoid unnecessary joins and over-fetching
- cache only after measuring a real bottleneck
- minify built assets in production
- know how to run the app locally, seed data, and deploy it without guesswork

Basic deployment literacy matters:

- PHP-FPM or equivalent runtime setup
- web server routing
- environment configuration
- SSL
- database provisioning and backups
- Git hygiene

## Progression roadmap

Use this as a rough path, not dogma.

### Stage 1

- PHP basics
- MySQL basics
- simple CRUD app
- forms, validation, and rendering

### Stage 2

- MVC structure
- authentication and sessions
- Tailwind UI system
- reusable components

### Stage 3

- larger domain model such as CRM or admin platform
- REST endpoints
- file uploads
- deployment

### Stage 4

- optimization
- background jobs
- realtime features when justified
- testing discipline
- scaling decisions based on real load

## Common failure modes

Most developers get into trouble here for predictable reasons:

- they learn syntax but not system design
- they put logic in controllers because it feels faster
- they skip security until after the feature ships
- they build one-off UI instead of reusable patterns
- they reach for heavy frameworks before they understand the basics

Do not optimize for looking advanced. Optimize for shipping software that stays readable.

## Expected output style when using this skill

When responding to a task in this stack:

- be direct
- prefer concrete code over abstract advice
- explain architectural choices briefly when they matter
- keep examples production-minded, not toy examples pretending to be real
- point out security and maintainability issues early

The standard is simple: if the code works today but becomes painful next week, it is not good enough.
