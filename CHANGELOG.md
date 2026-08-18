# Oihana PHP Nginx library - Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)  
and follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Fixed

- `redirectBlock()` can send visitors to `http`. The scheme was written into the emitted line and could not be asked for, so every generated block redirected to `https` whether or not anything served it. On a site with no certificate the redirection pointed at an endpoint nothing was listening on — and where the application redirects back to its own canonical URL, the two closed a loop the visitor never got out of.

  The new `$scheme` parameter comes last and defaults to `UriScheme::HTTPS` — the enum `oihana/php-enums` already provides — so no existing call changes what it emits. An unknown scheme raises `InvalidArgumentException`, as an unknown direction already did.

### Changed

- `redirectBlock()`'s `@example` output described a `rewrite … permanent` form the function stopped emitting; it now shows the `return 301` block it actually produces.

- Dependencies: dropped the now-unused `oihana/php-system` requirement — `php-nginx` consumes no `php-system` namespace directly. Added `minimum-stability: dev` + `prefer-stable: true` so the focused split packages (`php-logging`, `php-traits`, …) pulled transitively through `oihana/php-commands` resolve cleanly now that they carry stable tags. No code or public-API change.

---

## [1.0.0] - 2025-08-xx

### Added

