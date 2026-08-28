# Task Manager API

A REST API for managing tasks, built from scratch in plain PHP (no framework) as a hands-on refresher on PHP fundamentals — routing, OOP, PDO, caching, middleware, and API security.

## Features

- Full CRUD for tasks (`GET`, `POST`, `PUT`, `DELETE`)
- Custom router with dynamic path segments (`/tasks/{id}`)
- MySQL persistence via PDO with prepared statements
- Redis caching layer (cache-aside pattern with delete-on-write invalidation)
- Structured logging via Monolog
- Middleware pipeline (request logging + route-level authentication)
- Centralized exception handling with consistent JSON error responses
- Input validation with detailed, field-level error messages

## Tech Stack

- **PHP 8.1+** (uses constructor property promotion, first-class callable syntax, readonly properties)
- **Composer** — dependency management, PSR-4 autoloading
- **MySQL** (via PDO)
- **Redis** (via the native `Redis` PHP extension)
- **Monolog** — logging

## Architecture

The project has no framework — routing, middleware, and dependency wiring are all hand-built to reinforce the underlying concepts.

```
public/
  index.php          # Composition root — wires dependencies, registers routes, dispatches
src/
  Cache/
    CacheInterface.php
    RedisCache.php
    CachedTaskRepository.php   # Decorator: adds caching around TaskRepository
  Database/
    Connection.php             # PDO connection wrapper
  Exceptions/
    ApiException.php           # Base exception (carries an HTTP status code)
    NotFoundException.php
    InvalidException.php
    ValidationException.php    # Carries a list of field-level errors
  Http/
    Request.php                # Immutable snapshot of the incoming request
    Response.php                # Knows how to send itself (status, headers, JSON body)
  Logging/
    LoggerInterface.php
    MonologAdapter.php          # Adapts Monolog to the project's own LoggerInterface
  Middleware/
    MiddlewareInterface.php
    LoggingMiddleware.php       # Global — logs every request/response
    AuthMiddleware.php          # Per-route — checks for an Authorization header
  Models/
    Task.php
  Repositories/
    TaskRepositoryInterface.php
    TaskRepository.php          # PDO-backed implementation
  Services/
    TaskService.php             # Filtering/searching over a list of tasks
    TaskValidator.php           # Input validation rules
  Router.php                    # Route matching + middleware composition
```

### Request lifecycle

1. `Request::fromGlobals()` builds an immutable `Request` object from PHP superglobals.
2. `Router::dispatch()` wraps the whole request in the global `LoggingMiddleware`.
3. `Router::handleRequest()` matches the path/method against registered routes.
4. If the matched route requires auth, the handler is wrapped in `AuthMiddleware` before being called.
5. The route handler returns a `Response` object, which is sent back to the client.
6. Any thrown exception is caught centrally and converted into a consistent JSON error response.

### Caching

`CachedTaskRepository` decorates `TaskRepository`, implementing the same `TaskRepositoryInterface` so callers can't tell the difference:

- **`getById`** — cache-aside: check Redis first, fall back to MySQL on a miss, populate the cache with a TTL.
- **`add` / `update` / `delete`** — write to MySQL first, then delete the corresponding cache key (delete-on-write invalidation).
- **`getAll`** — bypasses the cache entirely (avoids cache-key explosion and a wide invalidation blast radius for filtered/paginated list variants).

### Middleware

- **`LoggingMiddleware`** (global) — logs every incoming request and outgoing response.
- **`AuthMiddleware`** (per-route) — currently checks for the presence of an `Authorization` header; full JWT validation is planned.

Middleware is composed per-request via nested closures around the matched route handler, with middleware instances themselves built once at bootstrap in `index.php`.

## API Endpoints

| Method | Path | Auth required | Description |
|---|---|---|---|
| GET | `/tasks` | No | List all tasks |
| GET | `/tasks/{id}` | No | Get a single task |
| POST | `/tasks` | Yes | Create a task |
| PUT | `/tasks/{id}` | Yes | Update a task |
| DELETE | `/tasks/{id}` | Yes | Delete a task |

**Request body (POST / PUT):**
```json
{
  "title": "Write project README",
  "done": false
}
```

**Response shape:**
```json
{
  "message": "Task 5 is updated",
  "task": {
    "id": 5,
    "title": "Write project README",
    "done": false,
    "created_at": "...",
    "updated_at": "..."
  }
}
```

**Error responses:**
```json
{ "error": "Task 5 not found" }
```
```json
{ "errors": ["Title is required.", "done must be a boolean."] }
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

Database credentials are currently set in `src/Database/Connection.php`. **Before deploying anywhere public, move these to environment variables** (e.g. via a `.env` file with `vlucas/phpdotenv` or similar) rather than hardcoding them.

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

## Known limitations / in progress

- **Authentication** — `AuthMiddleware` currently only checks that an `Authorization` header is present, not that it's a valid token. Full JWT issuance/validation is in progress.
- **Database credentials** — currently hardcoded in `Connection.php`; should move to environment variables before any public deployment.
- **No automated tests yet** — PHPUnit test coverage is planned.

## Roadmap

- [ ] JWT-based authentication and token revocation
- [ ] Account-enumeration-safe error responses
- [ ] Unit tests (PHPUnit)
- [ ] Design pattern refactor pass (Singleton/Factory where appropriate)
- [ ] Standardized JSON response formatting
- [ ] Final documentation polish

## License

MIT
