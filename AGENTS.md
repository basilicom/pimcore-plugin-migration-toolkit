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
- **Helper wiring**: `Migration\MigrationHelperFactory` (container service) creates the helpers with their
  collaborators; `Migration\HelperAwareMigrationFactory` decorates `doctrine.migrations.migrations_factory` and
  injects it into every `AbstractAdvancedPimcoreMigration`. The base class memoizes one helper per class and
  migration and sets the output writer. Without injection (hand-built migration, unit test) it falls back to
  `MigrationHelperFactory::standalone()`. A helper with a new dependency gets it through the factory constructor.
- **Fixture migration**: `tests/App/Migrations/Version20260101000000` (path in
  `tests/App/config/packages/doctrine_migrations.yaml`) exists only for the migrate command test, which reverts
  and re-executes it in child processes.
- **Test rig**: `docker-compose.yml` (PHP 8.4 + throwaway MariaDB), the bundle is the Composer root package,
  `tests/App` is a minimal Pimcore project (`Kernel.php`, `config/`, `bin/console`) installed by
  `docker/install.php` through the Installer *service* — the CLI installer wants a signed product key. The
  committed `tests/App/var/config/needs-install.lock` plus an empty encryption secret make the kernel skip
  the registration check. `tests/bootstrap.php` boots that kernel once for both suites.
- **Container user**: `make` runs the PHP container as `www-data` (`DOCKER_USER`); CI overrides it with `root`
  because the GitHub checkout belongs to the runner's uid and a bind mount does not translate ownership.
- **Quality gates**: `make lint` (PHP-CS-Fixer with the Basilicom ruleset, PHPStan level 6 over `src`, `tests`,
  `docker`) and `make test` (PHPUnit 11: `tests/Unit`, `tests/Functional`). CI runs both (`.github/workflows/ci.yml`).
- **Pimcore quirks the helpers work around**: config-like models keep deleted entries in the runtime cache
  (`AbstractMigrationHelper::forgetRuntimeCache()` after deletions); `CustomLayout::getByNameAndClassId()`
  yields a model whose DAO has no data source, so save/delete on it do nothing — reload by id first.

## Conventions

- `declare(strict_types=1)` in every file; full parameter and return types; PHPDoc generics for arrays.
- Command names are `basilicom:<area>:<verb>` and exposed as a `NAME` constant.
- Exceptions extend `Exceptions\MigrationToolkitException`.
- Constants instead of magic strings; no comments narrating changes.
- Every public helper method and every command has at least one test. Functional tests extend
  `Tests\Functional\AbstractFunctionalTestCase`, use `uniqueName()` for element names and register
  cleanup with `onTearDown()`; read persisted state back through Pimcore's model API.
- Saved configurations in the test app write to the settings store (`tests/App/config/packages/pimcore.yaml`),
  otherwise a symfony-config write is only visible after a container rebuild.
- Document behaviour changes in `README.md` (usage) and `CHANGELOG.md` (per version).
- Keep this file current when structure, commands or conventions change.

## Commands

| Command | Purpose |
|---|---|
| `basilicom:migrations:migrate-in-separate-processes` | Runs each pending Doctrine migration in its own PHP process; `--dry-run`, `--down=prev\|<version>`, `--bundle`, `--timeout`. Pending list from Doctrine's `DependencyFactory` (`doctrine.migrations.dependency_factory`) |
| `basilicom:translations:import <file>` | Imports a Pimcore translation CSV export into one domain (`--domain`, `--overwrite`, `--delimiter`) |
| `basilicom:translations:sync` | Adds Symfony YAML catalogue labels to Pimcore translations (`--domain`, `--catalogue-dir`, `--overwrite`) |

## Open Items (see README "Ideas" and the 7.0 review)

- `UserRolesMigrationHelper` workspace methods take 13 positional booleans; a value object is planned.
- Class/Objectbrick/Fieldcollection/CustomLayout helpers import JSON definitions; projects on Pimcore 11+
  usually commit `definition_*.php` files instead.
- **Dist**: `.gitattributes` marks tests, tooling, Docker rig and agent docs as `export-ignore`, so the Composer
  package (GitHub zipball) ships only `src/`, `composer.json`, `README.md`, `CHANGELOG.md` and the license.
