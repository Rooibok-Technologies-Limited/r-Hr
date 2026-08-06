<!--
  @author Bodo Desderio <rooiboktechltd@gmail.com>
  @copyright 2026 Rooibok Technologies. All rights reserved.
-->
# PROOF — Navigation Restructure + Full-System Audit (branch audit/full-system-20260806)

## Baseline (Phase 0 — captured before any change on this branch)

| Measure | Value | Command |
|---|---|---|
| Tracked files | 3416 | `git ls-files \| wc -l` |
| app/ PHP files | 1077 | `git ls-files 'app/*.php' \| wc -l` |
| PHP lint failures (app/) | **0** | `php -l` sweep, PHP 8.2 in `rhr_app` |
| Route definitions | 982 lines | `grep -c 'routes->' app/Config/Routes.php` |
| ERP controllers / models / views / JS modules | 78 / 107 / 423 / 165 | `ls`/`git ls-files` counts |
| Migrations | 22 | `ls app/Database/Migrations` |
| Unit/Api test files | 5 (no phpunit runner installed) | `tests/Unit`, `tests/Api` |
| App boots + serves | **yes** (HTTP 200 /erp/login) | `curl` |
| Static GET route probe (prior session, same code) | 247 routes, 0 unexpected non-200/307 | scratchpad/probe.sh |
| AJAX `*_list` probe (prior session) | 117 endpoints, 0 failures | scratchpad/probe.sh |

Environment: PHP 8.2 (docker `rhr_app`), Postgres 16 (`rhr_postgres`), nginx (`rhr_nginx`,
host port 12000), beanstalkd + worker. Dev DB seeded with companies 2 (Acme/GBP), 8 (Demo),
10 (Lira Digital); roles: company owner `kelly.flynn`, staff `testanalyst` (QA Tester:
attendance+leave), `superadmin`.

_Gate table, defect register, coverage map: appended at Phase 8._
