# Oihana PHP Nginx library - Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)  
and follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Changed

- Dependencies: dropped the now-unused `oihana/php-system` requirement — `php-nginx` consumes no `php-system` namespace directly. Added `minimum-stability: dev` + `prefer-stable: true` so the focused split packages (`php-logging`, `php-traits`, …) pulled transitively through `oihana/php-commands` resolve cleanly now that they carry stable tags. No code or public-API change.

---

## [1.0.0] - 2025-08-xx

### Added

