# Pimcore Plugin Migration Toolkit
License: MIT — see [LICENSE.md](LICENSE.md)

## Version information

| Bundle Version | PHP  | Pimcore                            |
|----------------|------|------------------------------------|
| ^3.0           | ^7.4 | ^6.8    |
| ^4.0           | ^8.0 | ^10.0   |
| ^5.0           | ^8.1 | ^11.0   |
| ^6.0           | ^8.3 | ^12.0   |
| ^7.0           | ^8.3 | ^12.0 (Platform Version 2025.x)    |

## Upgrade to 7.0

* Helpers no longer flush the whole Pimcore cache. Pimcore's own `save()` calls invalidate what they touch
  (`class_<id>`, `customlayout_<id>`, `output`, classification store and user runtime caches), so the
  `ClearCacheTrait` and its `Cache::clearAll()` are gone. `basilicom:migrations:migrate-in-separate-processes`
  does not clear the cache before running either.
* `basilicom:import:translations` became `basilicom:translations:import` with `--domain`, `--overwrite` and
  `--delimiter` (see [Commands](#commands)). `TranslationService` and `InvalidTranslationFileFormatException`
  were replaced by `Translation\TranslationImporter` and `Translation\Exception\InvalidTranslationFileException`.
* `TranslationMigrationHelper::addTranslations()` takes an `Overwrite` mode and returns an `ImportResult`.
* All parameters are typed; `UserRolesMigrationHelper` declares its nullable strings explicitly (PHP 8.4).
* `CustomLayoutMigrationHelper` expects a `DecoderInterface` instead of a `SerializerInterface`.

## Why?

In every project we have migrations for the same things. Like Thumbnails, Classes, etc.

This plugin provides you with the migration helpers and further tools.

## Installation

```
composer require basilicom/pimcore-plugin-migration-toolkit
```

### Activate Plugin

* Add to config/bundles.php
``` 
return [
    ...
    PimcorePluginMigrationToolkitBundle::class => ['all' => true],
];

```

## Usage Migration Helpers

For all migrations extend them from the class ```AbstractAdvancedPimcoreMigration```.

### Migration Data

A migration that needs files (class exports, SQL, assets) reads them from a folder next to it,
named after the migration class:

```
migrations/Version20260101120000.php
migrations/data/Version20260101120000/class_Product_export.json
migrations/data/Version20260101120000/sql/create.sql
```

`$this->getDataFolder()` returns that path.

### Website Settings

Example: Up

```php 
$websiteSettingsMigrationHelper = $this->getWebsiteSettingsMigrationHelper();
$websiteSettingsMigrationHelper->createOfTypeText('text', 'text hier');
$websiteSettingsMigrationHelper->createOfTypeDocument('document', 1);
$websiteSettingsMigrationHelper->createOfTypeAsset('asset', 1);
$websiteSettingsMigrationHelper->createOfTypeObject('object', 1);
$websiteSettingsMigrationHelper->createOfTypeBool('bool', false);
```

Example: Down

```php
$websiteSettingsMigrationHelper = $this->getWebsiteSettingsMigrationHelper();
$websiteSettingsMigrationHelper->delete('text');
$websiteSettingsMigrationHelper->delete('document');
$websiteSettingsMigrationHelper->delete('asset');
$websiteSettingsMigrationHelper->delete('object');
$websiteSettingsMigrationHelper->delete('bool');
```

### Static Routes

Example: Up

```php 
$staticRoutesMigrationHelper = $this->getStaticRoutesMigrationHelper();
$staticRoutesMigrationHelper->create(
    'route',
    '/pattern',
    '/reverse',
    'controller',
    'variable1,variable2',
    'default1,default2',
    10
);
$staticRoutesMigrationHelper->create(
    'route1',
    '/pattern1',
    '/reverse1',
    'controller1'
);
```

Example: Down

```php
$staticRoutesMigrationHelper = $this->getStaticRoutesMigrationHelper();
$staticRoutesMigrationHelper->delete('route');
$staticRoutesMigrationHelper->delete('route1');
```

### User Roles

There is no way to remove the workspaces (dataobjects, documents or assets).

Even when deleting a user role in the pimcore backend the workspace data stays in the database.

Example: Up

```php 
$roleName = 'migrationRole';
$path = '/';

$userRolesMigrationHelper = $this->getUserRolesMigrationHelper();
$userRolesMigrationHelper->create(
    $roleName,
    ['dashboards', 'admin_translations'],
    ['doctype'],
    ['class'],
    ['de', 'en'],
    ['de']
);

$userRolesMigrationHelper->addWorkspaceDataObject($roleName, $path, true, true, false, true, false, false, false, false, false, false, false, 'layout1,layout2', 'de,en', 'de,en');
$userRolesMigrationHelper->addWorkspaceDocument($roleName, $path, true, true, false, true, false, true, false, false, false, false, false);
$userRolesMigrationHelper->addWorkspaceAsset($roleName, $path, true, true, true, false, false, false, false, false, false);

// PHP 8 - Named Arguments with the same setting as above
$userRolesMigrationHelper->addWorkspaceDataObject($roleName, $path, list: true, view: true, publish: true, layouts: 'product_productproductpolo', lEdit: 'en,de,de_CH,fr_CH', lView: 'en,de,de_CH,fr_CH');
$userRolesMigrationHelper->addWorkspaceDocument($roleName, $path, list: true, view: true, publish: true);
$userRolesMigrationHelper->addWorkspaceAsset($roleName, $path, list: true, view: true, publish: true);

```

Example: Down

```php
$roleName = 'migrationRole';
$path = '/';
$userRolesMigrationHelper = $this->getUserRolesMigrationHelper();

$userRolesMigrationHelper->updateWorkspaceDataObject($roleName, $path, true, true, false, true, false, false, false, false, false, false, false, 'layout1,layout2', 'de,en', 'de,en');
$userRolesMigrationHelper->updateWorkspaceDocument($roleName, $path, true, true, false, true, false, false, false, false, false, false, false);
$userRolesMigrationHelper->updateWorkspaceAsset($roleName, $path, true, true, true, false, false, false, false, false, false);

// PHP 8 - Named Arguments with the same setting as above
$userRolesMigrationHelper->updateWorkspaceDataObject($roleName, $path, list: true, view: true, publish: true, layouts: 'product_productproductpolo', lEdit: 'en,de,de_CH,fr_CH', lView: 'en,de,de_CH,fr_CH');
$userRolesMigrationHelper->updateWorkspaceDocument($roleName, $path, list: true, view: true, publish: true);
$userRolesMigrationHelper->updateWorkspaceAsset($roleName, $path, list: true, view: true, publish: true);

$userRolesMigrationHelper->deleteWorkspaceDataObject($roleName, $path);
$userRolesMigrationHelper->deleteWorkspaceDocument($roleName, $path);
$userRolesMigrationHelper->deleteWorkspaceAsset($roleName, $path);

$userRolesMigrationHelper->delete($roleName);
```

### Bundle / Extension

Enabling a bundle is a change to `config/bundles.php`, so it cannot be done from a migration. Once
the bundle is registered there, a migration installs (or uninstalls) it and re-installs the assets.
With [migrate in separate processes](#migrate-in-separate-processes) every migration gets a freshly
booted kernel, so a bundle registered by one deploy step is visible to the migration that installs it.

Example: Up

```php
$this->getBundleMigrationHelper()->install(\Pimcore\Bundle\StaticRoutesBundle\PimcoreStaticRoutesBundle::class);
```

Example: Down

```php
$this->getBundleMigrationHelper()->uninstall(\Pimcore\Bundle\StaticRoutesBundle\PimcoreStaticRoutesBundle::class);
```

### Class Definitions

Example: Up

```php 
$className = 'testing';
$classDefinitionMigrationHelper = $this->getClassDefinitionMigrationHelper();
$jsonPath = $classDefinitionMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$classDefinitionMigrationHelper->createOrUpdate($className, $jsonPath);
```

Example: Down

```php
$className = 'testing';
$classDefinitionMigrationHelper = $this->getClassDefinitionMigrationHelper();
$classDefinitionMigrationHelper->delete($className);
// OR
$jsonPath = $classDefinitionMigrationHelper->getJsonDefinitionPathForDownMigration($className);
$classDefinitionMigrationHelper->createOrUpdate($className, $jsonPath);
```

### Objectbricks

Example: Up

```php 
$objectbrickName = 'brick';
$objectbrickMigrationHelper = $this->getObjectBrickMigrationHelper();
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForUpMigration($objectbrickName);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```

Example: Down

```php
$objectbrickName = 'brick';
$objectbrickMigrationHelper = $this->getObjectBrickMigrationHelper();
$objectbrickMigrationHelper->delete($objectbrickName);
// OR
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForDownMigration($objectbrickName);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```

### Fieldcollection

Example: Up

```php 
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldCollectionMigrationHelper();
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForUpMigration($key);
$fieldcollectionMigrationHelper->createOrUpdate($key, $jsonPath);
```

Example: Down

```php
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldCollectionMigrationHelper();
$fieldcollectionMigrationHelper->delete($key);
// OR
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForDownMigration($key);
$fieldcollectionMigrationHelper->createOrUpdate($key, $jsonPath);
```

### Classification Store

Classification Stores cannot be created with given ID.

But the ID is needed for the store field in a class.

Therefore using the name is needed.

But the name is not unique, so always use a unique name.

So be aware of that.

Example: Up

```php
$groupName = 'GroupName';
$fieldName = 'FieldName';
$title = 'Title fo FieldName';

$classificationStoreMigrationHelper = $this->getClassificationStoreMigrationHelper();
$storeConfig = $classificationStoreMigrationHelper->createOrUpdateStore(
    'StoreName',
    'Description'
);

$storeId = (int) $storeConfig->getId();

$classificationStoreMigrationHelper->createOrUpdateGroup(
    $groupName,
    'Description',
    $storeId
);

// Input
$definition = new ClassDefinitionData\Input();
$definition->setWidth(500);
$definition->setName($fieldName);
$definition->setTitle($title);

$classificationStoreMigrationHelper->createOrUpdateKey(
    $fieldName,
    $title,
    'Description',
    $definition,
    $storeId,
    $groupName
);
```

Example: Down

```php
$storeName = 'StoreName';
$classificationStoreMigrationHelper = $this->getClassificationStoreMigrationHelper();
$storeConfig = $classificationStoreMigrationHelper->getStoreByName($storeName);
$storeId = (int) $storeConfig->getId();
$classificationStoreMigrationHelper->deleteGroup($groupName, $storeId);
$classificationStoreMigrationHelper->deleteKey($fieldName, $storeId);
$classificationStoreMigrationHelper->deleteStore($storeName);
```

## FieldDefinition Examples
```php
// Textarea
$definition = new ClassDefinitionData\Textarea();
$definition->setWidth(500);
$definition->setHeight(100);
$definition->setShowCharCount(true);
$definition->setName($fieldName);
$definition->setTitle($title);

// Select
$definition = new ClassDefinitionData\Select();
$definition->setWidth(500);
$definition->setDefaultValue('');
$definition->setOptions([]);
$definition->setName($fieldName);
$definition->setTitle($title);

```

### Custom Layouts

Custom Layouts will get the id like "lower(<classId><name>)".

```php 
const CUSTOM_LAYOUT = [
    'classId' => 'reference',
    'name' => 'readOnly'
];
``` 

Example: Up

```php 
$customLayoutMigrationHelper = $this->getCustomLayoutMigrationHelper();
$jsonPath = $customLayoutMigrationHelper->getJsonDefinitionPathForUpMigration(self::CUSTOM_LAYOUT['name'], self::CUSTOM_LAYOUT['classId']);
$customLayoutMigrationHelper->createOrUpdate(
    self::CUSTOM_LAYOUT['name'],
    self::CUSTOM_LAYOUT['classId'],
    $jsonPath
);
```

Example: Down

```php
$customLayoutMigrationHelper = $this->getCustomLayoutMigrationHelper();
$customLayoutMigrationHelper->delete(
    self::CUSTOM_LAYOUT['name'],
    self::CUSTOM_LAYOUT['classId']
);
// OR
$jsonPath = $customLayoutMigrationHelper->getJsonDefinitionPathForDownMigration(self::CUSTOM_LAYOUT['name'], self::CUSTOM_LAYOUT['classId']);
$customLayoutMigrationHelper->createOrUpdate(
    self::CUSTOM_LAYOUT['name'],
    self::CUSTOM_LAYOUT['classId'],
    $jsonPath
);
```

### Document (Page)

```php 
const PAGE = [
    'key' => 'diga',
    'name' => 'DiGA',
    'controller' => 'Search',
    'parentPath' => '/',
];
``` 

Example: Up

```php 
$documentMigrationHelper = $this->getDocumentMigrationHelper();
$documentMigrationHelper->createPageByParentPath(
    self::PAGE['key'],
    self::PAGE['name'],
    self::PAGE['controller'],
    self::PAGE['parentPath']
);
```

Example: Down

```php
$documentMigrationHelper = $this->getDocumentMigrationHelper();
$documentMigrationHelper->deleteByPath(
    self::PAGE['parentPath'].self::PAGE['key']
);
```

### Object (Folder)

Example: Up

```php 
$dataObjectMigrationHelper = $this->getDataObjectMigrationHelper();
$dataObjectMigrationHelper->createFolderByParentId('folder1', 1);
$dataObjectMigrationHelper->createFolderByPath('/folder2/subfolder');
```

Example: Down

```php
$dataObjectMigrationHelper = $this->getDataObjectMigrationHelper();
$dataObjectMigrationHelper->deleteById(2);
$dataObjectMigrationHelper->deleteByPath('/folder2');
```

### Asset (File)
Example: Up
``` 
$assetMigrationHelper = $this->getAssetMigrationHelper();
$newAsset = $assetMigrationHelper->createAsset('./project/folder/asset.png', '/pimcore/asset/folder', 'myAsset.png');
$assetMigrationHelper->updateAsset($newAsset, './project/folder/updatedAsset.png', 'updatedAsset.png');
```
Example: Down
```
$assetMigrationHelper = $this->getAssetMigrationHelper();
$assetMigrationHelper->deleteById(2);
$assetMigrationHelper->deleteByPath('/pimcore/asset/folder/myAsset.png');
```

### Asset (Folder)

Example: Up

```php 
$assetMigrationHelper = $this->getAssetMigrationHelper();
$assetMigrationHelper->createFolderByParentId('name', 1);
$assetMigrationHelper->createFolderByPath('/asset1/subasset');
```

Example: Down

```php
$assetMigrationHelper = $this->getAssetMigrationHelper();
$assetMigrationHelper->deleteById(2);
$assetMigrationHelper->deleteByPath('/asset1');
```

### QuantityValue Unit

Example: Up

```php
$quantityValueUnitMigrationHelper = $this->getQuantityValueUnitMigrationHelper();
$quantityValueUnitMigrationHelper->createOrUpdate('uniqueid', 'abr', 'Long Abbreviation');
```

Example: Down

```php
$quantityValueUnitMigrationHelper = $this->getQuantityValueUnitMigrationHelper();
$quantityValueUnitMigrationHelper->delete('uniqueid');
```

### MySQL Helper

#### Example: Up

to load and execute a large sql file, do the following:

```php
$mysqlHelper = $this->getMySqlMigrationHelper();
$sqlFile = $mysqlHelper->loadSqlFile('your_sql_file.sql');
$this->addSql($sqlFile);
```
all sql files should be stored in the `sql` subdirectory within the migration's data directory:
```shell
project/src/Migrations/data/<YOUR_MIGRATIONS_CLASS_NAME>/sql
```

#### Example: Down
```php
$mysqlHelper = $this->getMySqlMigrationHelper();
$sqlFile = $mysqlHelper->loadSqlFile('your_sql_file.sql', $mysqlHelper::DOWN);
$this->addSql($sqlFile);
```

please keep in mind the changed path in case of down migrations:
```shell
project/src/Migrations/data/<YOUR_MIGRATIONS_CLASS_NAME>/sql/down
```

### Translation Helper

Adds translations to Pimcore's editable translations. The default domain is `messages` (shared translations);
any registered domain works, including `admin`.

```php
protected array $translations = [
    'translation.key' => [
        'en' => 'Test en',
        'de' => 'Test de',
    ],
];
```

#### Example: Up
```php
// overwrite labels that already exist (default)
$this->getTranslationMigrationHelper()->addTranslations($this->translations);

// keep labels editors already changed, add only the missing ones
$this->getTranslationMigrationHelper()->addTranslations($this->translations, 'messages', Overwrite::Never);
```

`addTranslations()` returns an `ImportResult` with the number of created keys, added and replaced labels and the
locales that were skipped because the domain does not know them.

#### Example: Down
```php
$this->getTranslationMigrationHelper()->removeTranslationsByKey(array_keys($this->translations));
```

## Commands

### Migrate in separate processes

Runs the pending Doctrine migrations like `doctrine:migrations:migrate`, but each one in its own PHP
process. A migration that changes class definitions or the container therefore never leaves the
following migrations of the same run with stale classes. Pending migrations come from Doctrine's
own status calculator, so the list matches `doctrine:migrations:status`.

```shell
bin/console basilicom:migrations:migrate-in-separate-processes
bin/console basilicom:migrations:migrate-in-separate-processes --dry-run          # list only
bin/console basilicom:migrations:migrate-in-separate-processes --bundle App       # class name prefix, -b
bin/console basilicom:migrations:migrate-in-separate-processes --timeout 0        # seconds per migration, 0 = none, -t
```

Reverting works the same way, one process per migration, newest first:

```shell
bin/console basilicom:migrations:migrate-in-separate-processes --down prev                                     # latest executed migration
bin/console basilicom:migrations:migrate-in-separate-processes --down 'App\Migrations\Version20260101120000'   # down to and including
bin/console basilicom:migrations:migrate-in-separate-processes --down prev --bundle App                        # latest one of that prefix
```

### Import Translations

Imports a CSV file in the format Pimcore exports under *Tools > Translations* (a `key` column plus one column
per locale). Existing labels are kept unless `--overwrite=always` is given; the CSV dialect is detected from the
file unless `--delimiter` is set. Locales the domain does not know are skipped and reported.

```shell
bin/console basilicom:translations:import /path/to/shared-translations.csv
bin/console basilicom:translations:import /path/to/admin-translations.csv --domain=admin --overwrite=always
bin/console basilicom:translations:import /path/to/translations.csv --delimiter=","
```

### Sync Translations

Brings the labels of the Symfony catalogues `translations/<domain>.<locale>.yaml` into Pimcore's editable
translations, so every key shows up under *Tools > Translations* after a deploy. Nested keys are flattened to
the dotted notation. Existing labels are kept by default — once a label is in the database, editors own it.

```shell
bin/console basilicom:translations:sync
bin/console basilicom:translations:sync --domain=messages --domain=admin
bin/console basilicom:translations:sync --catalogue-dir=/path/to/translations --overwrite=always
```

Directory and domains default to the bundle configuration:

```yaml
pimcore_plugin_migration_toolkit:
    translations:
        catalogue_dir: '%kernel.project_dir%/translations'
        domains: [messages]   # add admin when the classic admin UI bundle is installed
```

The CSV import, the sync and the `TranslationMigrationHelper` all write through
`Translation\TranslationImporter`, so they behave the same.

## Development

The bundle ships its own test rig: `docker-compose.yml` starts a PHP 8.4 container and a throwaway
MariaDB, the bundle itself is the Composer root package (so `vendor/` contains Pimcore) and
`tests/App` is a minimal Pimcore project that the tests boot against.

```shell
make setup            # start the containers, composer install, install Pimcore into tests/App
make test             # unit + functional tests (make test-unit / make test-functional)
make lint             # PHP-CS-Fixer dry run + PHPStan level 6 (make lint-php-fix applies the fixes)
make destroy          # remove containers and volumes
```

`docker/install.php` drives Pimcore's installer service directly: the CLI installer insists on a
signed product key, which a test rig does not have. With an empty encryption secret and the committed
`tests/App/var/config/needs-install.lock` marker the kernel skips the registration check.

Functional tests live in `tests/Functional`, extend `AbstractFunctionalTestCase`, create uniquely
named elements and register their removal with `onTearDown()`. Every public helper method has at
least one test. Saved configurations (static routes, custom layouts, …) use the settings store as
write target in `tests/App/config/packages/pimcore.yaml`, so they are readable in the same process.

## Ideas

* command: `basilicom:migrations:generate <type>` — scaffold a migration with its data folder, e.g. for a
  class definition export
