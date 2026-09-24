# Pimcore Plugin Migration Toolkit — Agent Context

## Core Facts

- **What it is**: a Pimcore bundle with migration helpers (`src/Helper/*MigrationHelper.php`), a base migration
  class (`Migration/AbstractAdvancedPimcoreMigration`) and console commands. Used by project migrations that extend
  the base class and call `$this->get<Name>MigrationHelper()`.
- **Stack**: PHP ≥ 8.3 · Pimcore ^12 (Symfony 6.4/7.x) · MIT license (`LICENSE.md`).
- **Namespace**: `Basilicom\PimcorePluginMigrationToolkit\` → `src/`.
- **Cache**: helpers must not call `Cache::clearAll()`. Pimcore's `save()` methods clear the tags they own
  (`class_<id>`, `customlayout_<id>`, `output`, classification store, user runtime cache). If something really
  needs invalidating, clear that tag only.
- **Translations**: `Translation\TranslationImporter` is the only place that writes into Pimcore translations.
  The CSV import command, the YAML catalogue sync and `TranslationMigrationHelper` all go through it with an
  `Overwrite` mode. Unknown locales are skipped and reported, never stored.
- **Bundle config**: `pimcore_plugin_migration_toolkit.translations.{catalogue_dir,domains}` in
  `DependencyInjection/Configuration`, exposed as container parameters and injected via `#[Autowire(param:)]`.
- **Helpers are not services yet**: `AbstractAdvancedPimcoreMigration` instantiates them with `new` and lazily.
  New helper dependencies have to be constructed there as well.
- **No tests, no lint config in this repo yet**. Until there is, run the consuming project's tools against
  `src/` (PHPStan level 6, PHP-CS-Fixer PSR-12 with the project's ruleset) and its functional test for
  `basilicom:translations:sync`.

## Conventions

- `declare(strict_types=1)` in every file; full parameter and return types; PHPDoc generics for arrays.
- Command names are `basilicom:<area>:<verb>` and exposed as a `NAME` constant.
- Exceptions extend `Exceptions\MigrationToolkitException`.
- Constants instead of magic strings; no comments narrating changes.
- Document behaviour changes in `README.md` (usage) and `CHANGELOG.md` (per version).
- Keep this file current when structure, commands or conventions change.

## Commands

| Command | Purpose |
|---|---|
| `basilicom:migrations:migrate-in-separate-processes` | Runs each pending Doctrine migration in its own PHP process (`--bundle`, `--timeout`) |
| `basilicom:translations:import <file>` | Imports a Pimcore translation CSV export into one domain (`--domain`, `--overwrite`, `--delimiter`) |
| `basilicom:translations:sync` | Adds Symfony YAML catalogue labels to Pimcore translations (`--domain`, `--catalogue-dir`, `--overwrite`) |

## Open Items (see README "Ideas" and the 7.0 review)

- `MigrateInSeparateProcessesCommand` still lists pending migrations by parsing `doctrine:migrations:list`
  output; should use Doctrine's `DependencyFactory`.
- `UserRolesMigrationHelper` workspace methods take 13 positional booleans; a value object is planned.
- Class/Objectbrick/Fieldcollection/CustomLayout helpers import JSON definitions; projects on Pimcore 11+
  usually commit `definition_*.php` files instead.
