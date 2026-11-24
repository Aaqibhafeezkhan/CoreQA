# Changelog

All notable changes to CoreQA are documented here.

## [Unreleased]

- Unified production and test autoloading against the lowercase `app/` directory for Linux deployments.
- Added PostgreSQL support for Vercel using Neon and automatic schema/index initialization.
- Added database-backed PHP sessions so authentication survives stateless Vercel function instances.
- Derived the production CSRF signing key from the database connection secret when no explicit key is configured.
- Removed the ephemeral Vercel SQLite fallback and fail safely with setup guidance when persistent storage is not connected.
- Replaced the GitHub Actions deployment workflow with Vercel's native Git integration; no GitHub deployment secrets are required.
- Updated the Vercel PHP runtime and added PostgreSQL integration coverage to the PHP 8.2 and 8.5 CI matrix.

## [1.0.0] - 2026-11-24

- Added a dependency-free PHP syntax checker and regression test runner.
- Added tests for Markdown escaping, safe links, CSRF validation, HTML escaping, routing, and production CSRF configuration.
- Added Composer project metadata and lint, test, and check scripts.
- Escaped user content inside the Markdown renderer and restricted link and image URL schemes.
- Added baseline content security, framing, MIME sniffing, referrer, permissions, and HSTS response headers.
- Added generic error responses and disabled displayed PHP errors in production.
- Added IP-scoped login and registration rate limits.
- Removed automatic local demo-account creation and the destructive legacy SQLite seed script.
- Resolved relative SQLite paths from the project root and created the storage directory when needed.
- Added SQLite indexes for topic ordering, answer lookup, and vote lookup.
- Documented architecture, security reporting, contribution setup, and current deployment limitations.
- Added the MIT license.
