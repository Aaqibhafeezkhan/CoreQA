# Security Policy

## Supported version

The \`main\` branch is the supported development version.

## Reporting a vulnerability

Use GitHub's private vulnerability reporting feature from the repository's Security tab by selecting **Report a vulnerability**. Do not disclose exploitable details in a public issue.

## Scope

Security reports are welcome for authentication, session handling, authorization, CSRF, Markdown rendering, output escaping, SQL access, request throttling, configuration, and deployment boundaries.

## Threat model

CoreQA is designed to protect account sessions, role-restricted management actions, user-submitted question and answer content, and database operations in a conventional PHP deployment. The implementation controls and verification status are summarized in the Security model table in [README.md](README.md).

## Known limitations

- The Markdown renderer supports a limited syntax subset and has focused regression tests, not a complete parser conformance suite.
- Database-backed integration tests are not yet included.
- The rate limiter uses the configured database and is not shared across separate application databases.
- The Vercel SQLite fallback uses ephemeral storage and must not be used for persistent production data.
- The mail queue worker does not deliver email.
- The application expects the deployment to keep the repository root outside the public document root and to configure HTTPS in production.
