# Oihana PHP Nginx library - Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)  
and follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- Three helpers that read a configuration, where the library could only write one. They come from
  an application built on this library, and had nothing of that application in them.
  - `nginxStatements()` splits a configuration into its instructions, one per entry, each keeping
    its `;`, `{` or `}`. Comments are dropped and blanks folded, so two files nginx reads the same
    way — a brace on its own line or not, any indentation, `x ;` or `x;` — give the same list.
    Quotes are honoured: a `#`, a `;` or a brace inside them belongs to the value. Three things
    nginx reads differently are documented rather than followed: a `#` inside a word, `${name}`
    and `\;` outside quotes.
  - `vhostAcmeRoot()` reads the `root` of a vhost's `location ^~ /.well-known/acme-challenge/`
    block — the directory `certbot --webroot` has to write into. The server's `root`, and one in a
    nested block, do not count.
  - `vhostCertificateName()` reads the name of the certbot certificate a vhost serves, from its
    `ssl_certificate` path under certbot's `live` directory (`/etc/letsencrypt/live` by default,
    a parameter otherwise). The name certbot gave a certificate is not always the site's.
- `composer coverage` and `composer coverage:md`, as in `oihana/php-files`: an HTML report and a
  Markdown summary in `build/coverage/`. The three helpers are covered in full; `NginxTrait`,
  which runs nginx itself, has no test yet.

### Fixed

- `redirectBlock()` can send visitors to `http`. The scheme was written into the emitted line and could not be asked for, so every generated block redirected to `https` whether or not anything served it. On a site with no certificate the redirection pointed at an endpoint nothing was listening on — and where the application redirects back to its own canonical URL, the two closed a loop the visitor never got out of.

  The new `$scheme` parameter comes last and defaults to `UriScheme::HTTPS` — the enum `oihana/php-enums` already provides — so no existing call changes what it emits. An unknown scheme raises `InvalidArgumentException`, as an unknown direction already did.

### Changed

- `redirectBlock()`'s `@example` output described a `rewrite … permanent` form the function stopped emitting; it now shows the `return 301` block it actually produces.

- `.gitignore`: `/build/`, `.phpunit.cache` and `.phpunit.result.cache`. A coverage run left its cache as a new file.

- Dependencies: dropped the now-unused `oihana/php-system` requirement — `php-nginx` consumes no `php-system` namespace directly. Added `minimum-stability: dev` + `prefer-stable: true` so the focused split packages (`php-logging`, `php-traits`, …) pulled transitively through `oihana/php-commands` resolve cleanly now that they carry stable tags. No code or public-API change.

---

## [1.0.0] - 2025-08-xx

### Added

