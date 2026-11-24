# CoreQA

A native PHP 8.2+ question-and-answer forum for small developer communities, built around PDO and a lightweight MVC-style core.

CoreQA supports question and answer workflows, tags, search, voting, and role-based moderation. It is a small application rather than a hosted community service: you provide the PHP runtime, database, and deployment environment.

## Features

- Account registration, login, logout, and password maintenance
- Questions and answers with ownership-aware editing
- Tags, search, and bounded pagination
- A small Markdown subset with escaped HTML and safe link handling
- Voting with one stored vote per user and answer
- Moderator and administrator actions guarded on the server
- CSRF tokens, session cookie protections, and request throttling
- SQLite for local development and MySQL-compatible schema for production

## Quick start

Run from the repository root:

\`\`\`bash
cp .env.example .env
php -S localhost:8080 -t public
\`\`\`

Open http://localhost:8080. The first visit initializes the local SQLite schema. Create an account through the registration page to start using the forum. Demo accounts are not created automatically.

## Requirements

- PHP 8.2 or newer
- PDO and the PDO driver for your chosen database
- Apache with mod_rewrite, Nginx with PHP-FPM, or PHP's built-in server for local development

## Quick start without Docker

Docker is not part of the current setup. For local development, use the PHP built-in server after creating the local environment file:

\`\`\`bash
cp .env.example .env
php -S localhost:8080 -t public
\`\`\`

Keep the repository root as the working directory so the relative SQLite path resolves to the project storage directory.

## Architecture

The application uses a small container and router rather than a framework. Controllers coordinate models and services, PDO handles persistence, and PHP views render the response.

\`\`\`mermaid
flowchart TD
    A[Web server] --> B[public/index.php]
    B --> C[Session and security headers]
    C --> D[Router]
    D --> E[Controller]
    E --> F[Service or model]
    F --> G[(PDO database)]
    E --> H[PHP view]
    H --> I[HTTP response]
\`\`\`

See [doc/ARCHITECTURE.md](doc/ARCHITECTURE.md) for the request lifecycle and design details.

## Security model

| Control | Implementation | Verification |
| --- | --- | --- |
| Password hashing and rehash | \`app/Models/User.php\`, \`app/Services/Auth.php\` | Reviewed, not covered by tests |
| Session ID regeneration and cookie flags | \`app/Services/Auth.php\`, \`public/index.php\` | Reviewed, not covered by tests |
| CSRF token validation and rotation | \`app/Services/Csrf.php\`, state-changing controllers | CSRF regression tests |
| HTML escaping | \`app/Core/Helpers.php\`, \`app/Services/Markdown.php\` | Helper and Markdown regression tests |
| Safe Markdown links and images | \`app/Services/Markdown.php\` | Markdown regression tests |
| Role and ownership checks | Controllers under \`app/Controllers/\` | Reviewed, not covered by tests |
| Request throttling | \`app/Services/RateLimiter.php\` | Reviewed, not covered by tests |
| Response headers and generic errors | \`public/index.php\` | Reviewed, not covered by tests |
| Production CSRF key requirement | \`config/config.php\` | Configuration regression tests |

See [SECURITY.md](SECURITY.md) for the supported version, reporting process, threat model, and known limitations.

## Testing

Run the dependency-free checks with:

\`\`\`bash
php bin/lint.php
php tests/run.php
\`\`\`

The lint script checks PHP syntax recursively. The test runner covers the Markdown renderer, HTML escaping, CSRF token handling, the router, and the production CSRF-key guard. Database-backed model tests and the rate-limiter window test are not included yet because the current test environment does not provide a PDO database driver.

## Configuration and deployment

Copy \`.env.example\` to \`.env\` and set the environment variables for the deployment. The default SQLite database path is relative to the repository root and is stored under \`storage/\`. For production, set \`APP_ENV=production\`, configure a random \`CSRF_KEY\` of at least 32 characters, use HTTPS, and configure a persistent MySQL or MariaDB database using [bin/schema.sql](bin/schema.sql).

The \`public/\` directory is the document root. Do not expose the repository root, \`.env\`, or \`storage/\` through the web server. See [doc/ARCHITECTURE.md](doc/ARCHITECTURE.md) for the deployment boundary.

The repository contains a Vercel PHP function entry point in \`api/index.php\` and routing in \`vercel.json\`. The Vercel configuration uses the community \`vercel-php\` runtime. Its SQLite fallback is \`/tmp/database.sqlite\`, which is ephemeral; it is not suitable for persistent forum data. Use an external persistent database for a deployed instance.

## Limitations

- The Markdown implementation is a small safe subset, not a complete CommonMark implementation.
- Email delivery is not implemented by the current mail queue worker.
- There is no automated database integration suite yet.
- The current rate limiter stores windows in the application database and is not a distributed rate-limit service.
- The included Vercel SQLite fallback does not persist data across function instances or deployments.

## Roadmap

- Add database-backed integration tests for SQLite and MySQL.
- Add PHPUnit and PHPStan as development-only quality tools.
- Implement email verification and password reset.
- Add accepted answers and notifications.
- Move rate limiting to shared storage for multi-instance deployments.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Contributions should include focused tests and a DCO sign-off.

## Author

Aaqibhafeez Khan · [GitHub](https://github.com/Aaqibhafeezkhan)

## License

MIT. See [LICENSE](LICENSE).
