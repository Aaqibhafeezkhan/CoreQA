# CoreQA Architecture

## Directory map

- `app/Controllers/`: request handlers for authentication, topics, answers, voting, account panels, and administration
- `app/Core/`: service container, database URL parser, router, view rendering, and shared helpers
- `app/Models/`: PDO-backed user, topic, answer, tag, and vote operations
- `app/Services/`: authentication, CSRF, durable database sessions, Markdown, caching, mail queue access, rate limiting, and slug generation
- `api/`: Vercel PHP function entry point
- `bin/`: MySQL schema and developer scripts
- `config/`: application settings, database setup, and route registration
- `public/`: web root and static assets
- `storage/`: local SQLite database and runtime cache

## Request lifecycle

1. The web server sends application requests to `public/index.php`.
2. The entry point loads configuration and creates the PDO connection. On Vercel it requires the `DATABASE_URL` supplied by the Neon integration.
3. The database bootstrap creates missing PostgreSQL tables and indexes idempotently. The SQLite schema is initialized for local development.
4. PostgreSQL sessions are registered with `DatabaseSessionHandler` before the PHP session starts, so login state is shared across Vercel function instances.
5. The entry point configures the session cookie, starts the session, and emits baseline response security headers.
6. The router matches the HTTP method and path against `config/routes.php`.
7. Controllers enforce login, role, ownership, and CSRF checks where required.
8. Controllers call models or services through the application container. Models use PDO prepared statements.
9. Controllers pass data to a PHP view, which is rendered inside the shared layout.

## Routing and container

Routes are registered explicitly in `config/routes.php`. The router supports GET and POST routes and named path parameters with regular-expression constraints. The container lazily creates service objects and receives the already initialized PDO connection from the request entry point. There is no Composer runtime autoloader or framework dependency.

## Authentication and sessions

Passwords are stored using PHP's `password_hash` API and verified with `password_verify`. Password hashes are rehashed on successful login when the default algorithm or options change. Login regenerates the session identifier. Session cookies are HttpOnly and SameSite=Lax; the secure flag is enabled for HTTPS and production. Logout is a POST route protected by CSRF validation.

On PostgreSQL deployments, PHP session payloads are base64-encoded and stored in the `app_sessions` table with a last-activity timestamp. This avoids relying on the ephemeral local filesystem of serverless functions. Local SQLite development continues to use PHP's default file-based session handler.

Login and registration throttling currently use the remote IP address. Login attempts are not yet independently throttled by account identifier. The application uses `REMOTE_ADDR`; it does not trust `X-Forwarded-For` directly.

## CSRF and throttling

The CSRF service stores a random session secret and derives the form token with HMAC-SHA-256. Validation uses `hash_equals`. Tokens rotate after successful authentication and password changes. State-changing application routes use POST and validate the token in their controller.

When `CSRF_KEY` is omitted, the application derives a stable signing key using HMAC-SHA-256 and the database connection secret. An explicit production key must be at least 32 characters and must not use the documented placeholder.

The rate limiter stores a count for an IP, route key, and fixed time window in the configured database. PostgreSQL stores IP bytes as `BYTEA`; the implementation encodes the bytes in PostgreSQL's hexadecimal format. PostgreSQL rate-limit increments use an atomic upsert so concurrent first requests do not race on the unique key. The limiter is suitable for a single shared application database, not independently configured databases across deployments.

## Database model

- `users`: account identity, password hash, role, and reputation
- `topics`: question owner, title, slug, body, status, and timestamps
- `posts`: answer owner, topic relationship, body, and timestamps
- `tags`: tag names and unique slugs
- `topic_tag`: many-to-many topic/tag relationship
- `votes`: one vote per user and answer, constrained to -1 or 1
- `rate_limits`: fixed-window request counts keyed by IP, route, and window start
- `app_sessions`: serialized PHP session payloads and last-activity timestamps

The PostgreSQL and SQLite bootstraps create tables and indexes when missing. PostgreSQL is the persistent Vercel deployment database; SQLite remains the zero-setup local development database. The MySQL schema is available for self-managed deployments.

## Vercel deployment

Import the GitHub repository into Vercel with the project root set to `./` and the framework preset set to Other. The `vercel.json` file selects the community `vercel-php` runtime and routes requests through `api/index.php`. Connect a Neon PostgreSQL store through the Vercel Marketplace; the integration provides `DATABASE_URL`. No GitHub Actions deployment token or manually maintained CSRF secret is needed. Vercel's native Git integration handles preview and production deployments.

The PHP runtime is community-maintained rather than an official Vercel runtime. Its pinned version includes the PDO PostgreSQL extension used by CoreQA.

## Markdown and escaping

The Markdown service escapes input before applying a deliberately small set of formatting rules. It supports emphasis, inline code, fenced code, links, and HTTP(S) images. Raw HTML remains text. Link targets are restricted to HTTP, HTTPS, and mailto schemes; image sources are restricted to HTTP and HTTPS. External links receive nofollow, noopener, and noreferrer attributes.

Templates use the shared `e()` helper for ordinary text and attribute output. Markdown output is rendered only through the Markdown service, which owns its escaping boundary.

## Design decisions and trade-offs

- Native PHP and PDO keep runtime dependencies small, at the cost of providing framework services directly in the application.
- SQLite simplifies local development. Neon PostgreSQL is the persistent Vercel database.
- The Markdown renderer favors a limited safe subset instead of allowing raw HTML or introducing a third-party parser.
- The database-backed fixed-window limiter avoids a separate cache service, but does not coordinate across separate application databases.
