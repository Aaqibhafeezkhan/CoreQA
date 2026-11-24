# Security Policy

## Supported version

The `main` branch is the supported development version.

## Reporting a vulnerability

Use GitHub's private vulnerability reporting feature from the repository's Security tab by selecting **Report a vulnerability**. Do not disclose exploitable details in a public issue.

## Scope

Security reports are welcome for authentication, session handling, authorization, CSRF, Markdown rendering, output escaping, SQL access, request throttling, configuration, and deployment boundaries.

## Threat model

CoreQA is designed to protect account sessions, role-restricted management actions, user-submitted question and answer content, and database operations in a conventional PHP deployment. The implementation controls and verification status are summarized in the Security model table in [README.md](README.md).

## Known limitations

- The Markdown renderer supports a limited syntax subset and has focused regression tests, not a complete parser conformance suite.
- Live Neon account integration testing is not yet included; CI exercises PostgreSQL-backed models, rate limiting, and sessions against a temporary PostgreSQL service.
- The rate limiter uses the configured database and is not shared across separate application databases.
- Vercel deployments require a persistent PostgreSQL connection supplied by the Neon Marketplace integration; the application does not use ephemeral filesystem storage for database records or login sessions.
- The Vercel PHP runtime is community-maintained, not an official Vercel runtime.
- The mail queue worker does not deliver email.
- The application expects HTTPS in production.
