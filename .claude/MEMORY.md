# Project Memory
Created: 2026-07-31

## [2026-08-01] — PHP 8.2 green-up + notification engine verified end-to-end
- Decisions:
  - Ran CI4 4.1.3 on PHP 8.2 by patching, not upgrading: `FILTER_SANITIZE_STRING`
    → `FILTER_SANITIZE_FULL_SPECIAL_CHARS` (4 `system/` files); `spark` masks
    `E_DEPRECATED`; `docker/php/php.ini` gets `output_buffering=4096` +
    `implicit_flush=Off`.
  - Run php-fpm as host uid via compose `app.user: "1000:1000"` (bind-mount owner)
    instead of chmod 777 on writable/.
  - Baselined `Add2faSupport` (columns pre-exist from init.sql) by inserting its
    `migrations` row, then `php spark migrate` for the prefs table.
- Patterns:
  - Blank HTTP 500 + NO CI log almost always = fpm can't write `writable/`
    (uid mismatch); it can't log the very error that broke it. Check the SAPI
    user vs `ls -ld writable`.
  - This container loads no main php.ini ("Loaded Configuration File => none");
    only `conf.d/*.ini` (custom.ini ← docker/php/php.ini). Web SAPI needs
    output_buffering set explicitly or it inherits CLI ob=0.
  - `php -i` measures the CLI SAPI (ob=0 always); use `php-fpm -i` for fpm.
  - CI4 resolves env from `$_SERVER['CI_ENVIRONMENT']` (default production);
    `spark` separately forced `error_reporting(-1)`, which is why only the CLI
    tripped on deprecations while the web path was fine.
- Gotchas:
  - A CI4 service used from the CLI QueueWorker must load its own helpers;
    BaseController's `$helpers` only run for web requests (system_setting()).
  - `Notifier::send()` `$channels` = list of names (`['inapp']`), not assoc.
  - USR2 graceful-reloads fpm but does NOT re-read php.ini system/perdir
    directives (output_buffering); a full container restart is required.
