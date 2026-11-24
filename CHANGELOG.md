# Changelog

All notable changes to CoreQA are documented here.

## [1.0.0] - 2026-11-24

- Added a dependency-free PHP syntax checker and regression test runner.
- Added tests for Markdown escaping, safe links, CSRF validation, HTML escaping, routing, and production CSRF configuration.
- Added Composer project metadata and lint, test, and check scripts.
- Escaped user content inside the Markdown renderer and restricted link and image URL schemes.
- Added baseline content security, framing, MIME sniffing, referrer, permissions, and HSTS response headers.
- Rejected the documented CSRF example placeholder in production configuration.
- Added generic error responses and disabled displayed PHP errors in production.
- Added IP and account-keyed login throttling.
- Added answer ownership and self-vote checks.
- Replaced unverified activity counters with database-backed answer counts.
- Documented architecture, security reporting, contribution setup, and current deployment limitations.
- Added the MIT license.
