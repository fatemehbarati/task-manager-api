# Task Manager API

A REST API for managing tasks, built from scratch in plain PHP (no framework) as a hands-on refresher on PHP fundamentals — routing, OOP, PDO, caching, middleware, JWT security, testing, and design patterns.

## Features

- Full CRUD for tasks (`GET`, `POST`, `PUT`, `DELETE`)
- Custom router with dynamic path segments (`/tasks/{id}`)
- MySQL persistence via PDO with prepared statements
- Redis caching layer (cache-aside pattern with delete-on-write invalidation)
- Structured logging via Monolog
- Middleware pipeline (request logging + route-level JWT authentication)
- JWT-based authentication (access + refresh tokens) with account-enumeration-safe error messaging
- Centralized exception handling with consistent, standardized JSON envelope responses
- Input validation with detailed, field-level error messages
- Unit tests (PHPUnit) for validation logic and middleware
- Singleton-managed database connection

## Tech Stack

- **PHP 8.1+** (uses constructor property promotion, first-class callable syntax, readonly properties, backed enums)
- **Composer** — dependency management, PSR-4 autoloading
- **MySQL** (via PDO)
- **Redis** (via the native `Redis` PHP extension)
- **Monolog** — logging
- **vlucas/phpdotenv** — loads secrets/config from `.env`
- **PHPUnit** — unit testing

## Architecture

The project has no framework — routing, middleware, and dependency wiring are all hand-built to reinforce the underlying concepts.

```
public/
  index.php          # Composition root — wires dependencies, registers routes, dispatches
src/
  Auth/
    JwtService.php             # Generates/validates access (15min) & refresh (30d) tokens
  Cache/
    CacheInterface.php
    RedisCache.php
    CachedTaskRepository.php   # Decorator: adds caching around TaskRepository
  Database/
    Connection.php             # PDO connection wrapper — Singleton (getInstance())
  Exceptions/
    ApiException.php           # Base exception (carries HTTP status + ErrorCode)
    NotFoundException.php
    InvalidException.php
    ValidationException.php    # Carries a list of field-level errors
    UnauthorizedException.php  # Fixed, enumeration-safe 401 message
    ErrorCode.php               # Backed enum: NOT_FOUND, VALIDATION_ERROR, UNAUTHORIZED, INTERNAL_ERROR, BAD_REQUEST
  Http/
    Request.php                # Immutable snapshot of the incoming request
    Response.php                # success()/failed() static factories; knows how to send itself
  Logging/
    LoggerInterface.php
    MonologAdapter.php          # Adapts Monolog to the project's own LoggerInterface
  Middleware/
    MiddlewareInterface.php
    LoggingMiddleware.php       # Global — logs every request/response
    AuthMiddleware.php          # Per-route — validates JWT access token
  Models/
    Task.php                    # Implements JsonSerializable (ISO 8601 dates in API output)
  Repositories/
    TaskRepositoryInterface.php
    TaskRepository.php          # PDO-backed implementation
  Services/
    TaskService.php             # Filtering/searching over a list of tasks
    TaskValidator.php           # Input validation rules
  Router.php                    # Route matching + middleware composition
tests/
  Services/
    TaskValidatorTest.php       # Pure-logic validation tests, incl. multibyte regression test
  Middleware/
    AuthMiddlewareTest.php      # Mocking practice with JwtService/LoggerInterface
```

### Request lifecycle

1. `Request::fromGlobals()` builds an immutable `Request` object from PHP superglobals.
2. `Router::dispatch()` wraps the whole request in the global `LoggingMiddleware`.
3. `Router::handleRequest()` matches the path/method against registered routes.
4. If the matched route requires auth, the handler is wrapped in `AuthMiddleware`, which validates the JWT access token before the handler runs.
5. The route handler returns a `Response` object (via `Response::success()`/`Response::failed()`), which is sent back to the client as a standardized JSON envelope.
6. Any thrown exception is caught centrally in `dispatch()` and converted into a consistent JSON error response, reading its status code and `ErrorCode` directly from the exception.

### Authentication (JWT)

- `POST /login` and `POST /refresh` are public. Login is currently stubbed (hardcoded credentials/`userId`) — there's no real `Users` table yet.
- Access tokens are short-lived (15 min); refresh tokens last 30 days. Each type is signed with its own secret.
- `AuthMiddleware` validates the access token on protected routes; a missing, expired, or tampered token returns a fixed, generic 401 (`UnauthorizedException`) to avoid leaking which part of the request was wrong.
- Secrets are loaded from `.env` via `vlucas/phpdotenv`.

**Not yet implemented:** token revocation (would need DB-backed token storage), refresh token rotation, and attaching the decoded `userId` from the token onto the request for handlers to use.

### Caching

`CachedTaskRepository` decorates `TaskRepository`, implementing the same `TaskRepositoryInterface` so callers can't tell the difference:

- **`getById`** — cache-aside: check Redis first, fall back to MySQL on a miss, populate the cache with a TTL.
- **`add` / `update` / `delete`** — write to MySQL first, then delete the corresponding cache key (delete-on-write invalidation).
- **`getAll`** — bypasses the cache entirely (avoids cache-key explosion and a wide invalidation blast radius for filtered/paginated list variants).

### Middleware

- **`LoggingMiddleware`** (global) — logs every incoming request and outgoing response.
- **`AuthMiddleware`** (per-route) — validates the JWT access token via `JwtService`.

Middleware is composed per-request via nested closures around the matched route handler, with middleware instances themselves built once at bootstrap in `index.php`.

### Design patterns

- **Singleton** — `Connection` guarantees exactly one instance (`private function __construct()` + `getInstance()`), with the underlying `PDO` itself lazily created separately inside `getConnection()`. Call site: `Connection::getInstance()->getConnection()`.
- **Adapter** — `MonologAdapter` adapts Monolog's `Logger` to the project's own `LoggerInterface`.
- **Decorator** — `CachedTaskRepository` wraps `TaskRepository` to add caching transparently.
- Factory was considered for repository selection and for the exception→response mapping in `dispatch()`, but rejected in both cases — neither had a genuine, already-existing type-based decision to centralize.

## API Endpoints

| Method | Path | Auth required | Description |
|---|---|---|---|
| POST | `/login` | No | Exchange (stubbed) credentials for an access + refresh token |
| POST | `/refresh` | No | Exchange a valid refresh token for a new access token |
| GET | `/tasks` | No | List all tasks |
| GET | `/tasks/{id}` | No | Get a single task |
| POST | `/tasks` | Yes | Create a task |
| PUT | `/tasks/{id}` | Yes | Update a task |
| DELETE | `/tasks/{id}` | Yes | Delete a task (returns `204`, no body) |

**Request body (POST / PUT):**
```json
{
  "title": "Write project README",
  "done": false
}
```

**Success response envelope:**
```json
{
  "data": {
    "id": 5,
    "title": "Write project README",
    "done": false,
    "created_at": "2026-09-16T10:32:00+00:00",
    "updated_at": "2026-09-16T10:32:00+00:00"
  }
}
```

**Error response envelope:**
```json
{
  "error": {
    "message": "Task 5 not found",
    "code": "NOT_FOUND"
  }
}
```

Validation errors additionally include a `details` field:
```json
{
  "error": {
    "message": "Validation failed",
    "code": "VALIDATION_ERROR",
    "details": ["Title is required.", "done must be a boolean."]
  }
}
```

## Setup

### Prerequisites

- PHP 8.1+
- Composer
- MySQL
- Redis (or Redis-compatible, e.g. Memurai on Windows)

### Installation

```bash
git clone https://github.com/<your-username>/task-manager-api.git
cd task-manager-api
composer install
```

### Configuration

Copy `.env.example` to `.env` and fill in your own values:

```
DB_HOST=127.0.0.1
DB_NAME=task_manager
DB_USER=root
DB_PASS=
JWT_ACCESS_SECRET=change-me
JWT_REFRESH_SECRET=change-me-too
```

Database credentials and JWT secrets are loaded from `.env` via `vlucas/phpdotenv` — nothing is hardcoded in source.

Create the `tasks` table:

```sql
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    done TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
```

Make sure Redis is running locally on the default port (`127.0.0.1:6379`), and that a `storage/logs/` directory exists and is writable (for Monolog's file handler).

### Running locally

```bash
php -S localhost:8000 -t public
```

The API is then available at `http://localhost:8000/tasks`.

### Running tests

```bash
./vendor/bin/phpunit
```

## Known limitations / in progress

- **No real user accounts** — `/login` uses stubbed credentials and a hardcoded `userId`; there's no `Users` table yet.
- **No token revocation** — logging out or invalidating a token before it expires isn't supported (would need DB-backed token storage).
- **No refresh token rotation** — a refresh token stays valid for its full 30-day life.
- **Decoded `userId` isn't attached to the request** — route handlers can't yet identify which user is making a request.
- **Test coverage is partial** — `TaskValidator` and `AuthMiddleware` are covered; repository, service, and route-handler tests aren't written yet.

## Roadmap

- [x] JWT-based authentication and account-enumeration-safe error responses
- [x] Unit tests (PHPUnit) for validation and middleware
- [x] Design pattern refactor pass (Singleton on `Connection`)
- [x] Standardized JSON response formatting (success/error envelope, ISO 8601 dates)
- [ ] Token revocation strategy
- [ ] Refresh token rotation
- [ ] Real `Users` table + credential checking
- [ ] Final documentation polish
- [ ] Dockerize the project (PHP-FPM, docker-compose with Nginx/MySQL/Redis)

## License

MIT