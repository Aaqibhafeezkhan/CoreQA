# CoreQA Architecture

## Directory map

- \`app/Controllers/\`: request handlers for authentication, topics, answers, voting, account panels, and administration
- \`app/Core/\`: service container, router, view rendering, and shared helpers
- \`app/Models/\`: PDO-backed user, topic, answer, tag, and vote operations
- \`app/Services/\`: authentication, CSRF, Markdown, caching, mail queue access, rate limiting, and slug generation
- \`app/Views/\`: PHP templates
- \`api/\`: Vercel PHP function entry point
- \`bin/\`: MySQL schema and developer scripts
- \`config/\`: application settings, database setup, and route registration
- \`public/\`: web root and static assets
- \`storage/\`: local SQLite database and runtime cache

## Request lifecycle

1. The web server sends application requests to \`public/index.php\`.
2. The entry point loads configuration, configures the session cookie, starts the session, and emits baseline response security headers.
3. The router matches the HTTP method and path against \`config/routes.php\`.
4. The controller enforces login, role, ownership, and CSRF checks where required.
5. Controllers call models or services through the application container.
6. Models use PDO prepared statements for database operations.
7. Controllers pass data to a PHP view, which is rendered inside the shared layout.

## Routing and container

Routes are registered explicitly in \`config/routes.php\`. The router supports GET and POST routes and named path parameters with regular-expression constraints. The container lazily creates the PDO connection and service objects. There is no Composer runtime autoloader or framework dependency.

## Authentication and sessions

Passwords are stored using PHP's \`password_hash\` API and verified with \`password_verify\`. Password hashes are rehashed on successful login when the default algorithm or options change. Login regenerates the session identifier. Session cookies are HttpOnly and SameSite=Lax; the secure flag is enabled for HTTPS and production. Logout is a POST route protected by CSRF validation.

Login throttling uses both the remote IP and a hash of the submitted account identifier. The application uses \`REMOTE_ADDR\`; it does not trust \`X-Forwarded-For\` directly. Registration is throttled by remote IP.

## CSRF and throttling

The CSRF service stores a random session secret and derives the form token with HMAC-SHA-256. Validation uses \`hash_equals\`. Tokens rotate after successful authentication and password changes. State-changing application routes use POST and validate the token in their controller.

The rate limiter stores a count for an IP, route key, and fixed time window in the configured database. It is suitable for a single application database, not a distributed multi-region deployment.

## Markdown and escaping

The Markdown service escapes input before applying a deliberately small set of formatting rules. It supports emphasis, inline code, fenced code, links, and HTTP(S) images. Raw HTML remains text. Link targets are restricted to HTTP, HTTPS, and mailto schemes; image sources are restricted to HTTP and HTTPS. External links receive nofollow, noopener, and noreferrer attributes.

Templates use the shared \`e()\` helper for ordinary text and attribute output. Markdown output is rendered only through the Markdown service, which owns its escaping boundary.

## Data model

- \`users\`: account identity, password hash, role, and reputation
- \`topics\`: question owner, title, slug, body, status, and timestamps
- \`posts\`: answer owner, topic relationship, body, and timestamps
- \`tags\`: tag names and unique slugs
- \`topic_tag\`: many-to-many topic/tag relationship
- \`votes\`: one vote per user and answer, constrained to -1 or 1
- \`rate_limits\`: fixed-window request counts keyed by IP, route, and window start

Foreign keys and unique constraints enforce the main relationships in the SQLite initializer and MySQL schema. The SQLite initializer enables foreign-key enforcement for its connection.

## Design decisions and trade-offs

- Native PHP and PDO keep runtime dependencies small, at the cost of providing framework services directly in the application.
- SQLite simplifies local development. MySQL or MariaDB is the intended persistent production database.
- The Markdown renderer favors a limited safe subset instead of allowing raw HTML or introducing a third-party parser.
- The database-backed fixed-window limiter avoids a separate cache service, but does not coordinate across independently configured databases.
- Vercel support is retained as an optional PHP function entry point. Its local SQLite fallback is ephemeral and is not a persistent production storage strategy.
