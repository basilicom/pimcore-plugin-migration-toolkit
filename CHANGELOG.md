# Changelog

## 7.0.0 (2026-09-30)

### Changed
* Helpers no longer flush the whole Pimcore cache: `ClearCacheTrait` and every `Cache::clearAll()` call are
  removed, Pimcore's own `save()` methods invalidate what they touch. `basilicom:migrations:migrate-in-separate-processes`
  no longer clears the cache before running.
* `basilicom:import:translations` is now `basilicom:translations:import` with `--domain`, `--overwrite=never|always`
  and `--delimiter`; `TranslationService` and `InvalidTranslationFileFormatException` are replaced by
  `Translation\TranslationImporter` and `Translation\Exception\InvalidTranslationFileException`.
* `TranslationMigrationHelper::addTranslations()` takes an `Overwrite` mode and returns an `ImportResult`.
* `CustomLayoutMigrationHelper` expects a `DecoderInterface`.
* `declare(strict_types=1)` and full parameter types everywhere; the nullable string parameters of
  `UserRolesMigrationHelper` are declared explicitly (PHP 8.4 deprecation).
* Targets Pimcore 12, i.e. `pimcore/platform-version ^2025.x`, which pins `pimcore/pimcore` to `^12.0`;
  `symfony/translation` and `symfony/yaml` are required explicitly.

### Fixed
* `CustomLayoutMigrationHelper::delete()` never removed anything: Pimcore's `getByNameAndClassId()`
  returns a model whose DAO does not know the data source, so `delete()` was a silent no-op. The
  helper now reloads the layout by id before saving or deleting.
* Deleting static routes, translations, website settings, units, classification store entries or
  definitions left the removed entry in Pimcore's runtime cache, so a `create()` later in the same
  migration still saw it. The helpers drop the runtime cache after a deletion.

### Added
* Test rig (`make setup && make test`): Docker Compose with MariaDB, a minimal Pimcore project in
  `tests/App`, PHPUnit 11 unit and functional suites covering every public helper method and command,
  PHPStan level 6 and PHP-CS-Fixer configuration, GitHub Actions workflow.
* `basilicom:translations:sync` adds the labels of the Symfony YAML catalogues (`<domain>.<locale>.yaml`) to
  Pimcore's editable translations without overwriting existing ones. Catalogue directory and domains are
  configurable under `pimcore_plugin_migration_toolkit.translations` (default: `messages`; the `admin` domain
  only exists with the classic admin UI bundle).
* `Translation\TranslationImporter` as the single write path for CSV import, catalogue sync and migration helper;
  locales a domain does not know are skipped and reported instead of stored.

## 6.1.0
* Relicensed from GPL-3.0-or-later to MIT.
