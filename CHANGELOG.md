# Changelog

All notable changes to inboxili/inboxili (PHP) are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/) and the project uses [Semantic Versioning](https://semver.org/).

## [0.1.1] - 2026-10-08

### Fixed
- Removed the `curl_close()` call, which raises a deprecation notice on PHP 8.5 and has had no effect since PHP 8.0.

## [0.1.0] - 2026-10-07

### Added
- Initial release: send a transactional email through `POST /api/v1/transactional/send`, typed errors, opt-in 429 retry, and webhook signature verification.
