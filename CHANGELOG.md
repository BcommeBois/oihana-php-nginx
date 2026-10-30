# Oihana PHP Nginx library - Change Log

All notable changes to this project will be documented in this file.

This project adheres to [Keep a Changelog](https://keepachangelog.com/en/1.0.0/)  
and follows [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### Added

- **Reading a configuration**, where the library could only write one. Three helpers in
  `oihana\nginx\helpers`, taken from an application built on this library and with nothing of
  that application left in them:
  - `nginxStatements()` splits a configuration into its instructions, one per entry, each keeping
    its `;`, `{` or `}`. Comments are dropped and blanks folded, so two files nginx reads the same
    way — a brace on its own line or not, any indentation, `x ;` or `x;` — give the same list.
    Quotes are honoured: a `#`, a `;` or a brace inside them belongs to the value. Three things
    nginx reads differently are documented rather than followed: a `#` inside a word, and
    `${name}` and `\;` outside quotes.
  - `vhostAcmeRoot()` reads the `root` of a vhost's `location ^~ /.well-known/acme-challenge/`
    block: the directory `certbot --webroot` has to write into. The server's `root`, and one in a
    nested block, do not count.
  - `vhostCertificateName()` reads the name of the certbot certificate a vhost serves, from its
    `ssl_certificate` path under certbot's `live` directory (`/etc/letsencrypt/live` by default,
    a parameter otherwise). The name certbot gave a certificate is not always the site's.
- **Reference configurations** in `config/nginx/`: an `nginx.conf`, and snippets for compression
  types, hidden files, `robots.txt`, static assets (fonts, images, media, service worker) and
  WordPress (headers, cache map, security rules for plugins, themes, uploads and sensitive files,
  static files, Yoast SEO). They are files to read and copy, not loaded by any code.
- **Tests for `NginxTrait`**, which had none. A test class uses the trait with `system()`,
  `makeFile()`, `deleteFile()`, `info()` and `warning()` replaced by recorders: nothing is run, no
  sudo, nothing written outside a temporary directory. They hold the commands each method asks
  for, the paths, when `sudo` applies, the messages, and the refusal of a missing
  `sites-available` or `sites-enabled`. The line choosing between `brew` and `systemctl` counts as
  covered, but only the branch of the machine running the tests is exercised.
- **`composer coverage` and `composer coverage:md`**, as in `oihana/php-files`: an HTML report and
  a Markdown summary in `build/coverage/`. The library is covered in full, every line of `src/`.

### Changed

- **Dependencies follow the split of `oihana/php-system`** into focused packages: the constants
  traits come from `oihana/php-reflect` (namespace `oihana\reflect`, formerly
  `oihana\reflections`), the enums from `oihana/php-enums`, and `isMac()` from `oihana/php-core`
  (`oihana\core\env\isMac`, formerly `oihana\files\isMac`). `oihana/php-exceptions` is required.
  The `oihana/php-system` requirement itself is dropped: nothing here uses it any more.
  `minimum-stability: dev` with `prefer-stable: true` lets the split packages pulled in through
  `oihana/php-commands` resolve now that they carry stable tags. No public API changes.
- `composer.lock` is no longer committed, as for a library.
- `NginxTrait`: its members follow the house order — the traits it uses, the constants (public,
  then protected), the property, the public methods alphabetically, then the protected one.
  `initializeNginxOptions()`'s documentation described options for a WordPress install; it now
  describes the `nginx` options it reads. No behaviour changes.
- `redirectBlock()`'s `@example` showed a `rewrite … permanent` form the function no longer
  emits; it now shows the `return 301` block it produces.
- `.gitignore` leaves out `/build/`, `.phpunit.cache` and `.phpunit.result.cache`: a coverage run
  left its cache as a new file.

### Fixed

- **`redirectBlock()` can send visitors to `http`.** The scheme was written into the emitted line
  and could not be asked for, so every generated block redirected to `https` whether or not
  anything served it. On a site with no certificate the redirection pointed at an endpoint
  nothing was listening on — and where the application redirects back to its own canonical URL,
  the two closed a loop the visitor never got out of. The new `$scheme` parameter comes last and
  defaults to `UriScheme::HTTPS`, so no existing call changes what it emits. An unknown scheme
  raises `InvalidArgumentException`, as an unknown direction already did.
- **The `RedirectDirection` tests never ran.** Their file was named `RedirectDirection.php`, and
  PHPUnit only reads files ending in `Test.php`: twelve tests, written in the first release, had
  never been run. Renamed `RedirectDirectionTest.php`; they pass.

---

## [0.0.1] - 2025-08-12

### Added

- `NginxTrait`, built on `oihana/php-commands`, to drive nginx from PHP:
  - `nginxConfigCreate()`, `nginxConfigExists()` and `nginxConfigDelete()` write, check and remove
    a vhost in `sites-available` and its link in `sites-enabled` ;
  - `nginxTest()` runs `nginx -t` ;
  - `nginxReload()`, `nginxRestart()`, `nginxStart()` and `nginxStop()` drive the service, with
    `systemctl` on Linux and `brew services` on macOS ;
  - `initializeNginxOptions()` sets the options every call starts from.
- `NginxOptions` and `NginxOption`: the nginx command-line options (`-c`, `-e`, `-g`, `-p`, `-q`,
  `-s`, `-t`, `-T`, `-v`, `-V`, `-h`), plus the library's own (`dir`, `enabled`, `sudo`, …), with
  `getSiteAvailableDirectory()` and `getSiteEnabledDirectory()`.
- `redirectBlock()`: the `if ( $host = … ) { return 301 … }` blocks that send a domain to its
  subdomain (`OUTBOUND`) or a subdomain to its domain (`INBOUND`).
- Enums: `NginxPath` (`sites-available`, `sites-enabled`, `snippets`), `NginxSignal` (`quit`,
  `reload`, `stop`) and `RedirectDirection` (`inbound`, `outbound`).
