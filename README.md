# Pimcore Plugin Migration Toolkit

## Version information

| Bundle Version | PHP | Pimcore |
| ----------- | -----------| ----------- |
| &lt; 4.x.x | ^7.4 | ^6.8 |
| &gt;= 4.x.x. | ^8.0 | ^10.0 |

## Why?

In every project we have migrations for the same things. Like Thumbnails, Classes, etc.

This plugin provides you with the migration helpers and further tools.

## Usage Migration Helpers

For all migrations extend them from the class ```AbstractAdvancedPimcoreMigration```.

### Migration Data

If a migration needs data it needs to be located in the following folder:
```<path/to/migrationFolder>/Migrations/data/<classname-of-the-migration>```

### System Settings

System Settings can be set via config.yaml.

Example: 

```yaml
pimcore_admin:
    branding:
        login_screen_invert_colors: true
        color_login_screen: '#001b36'
        color_admin_interface: '#001b36'
        login_screen_custom_image: '/build/images/backend/background-login-screen.jpg'
```


### Language Settings

Language Settings are part of the System Settings and can be set via config.yaml.

Example: 

```yaml
pimcore:
    general:
        timezone: Europe/Berlin
        redirect_to_maindomain: false
        language: en
        valid_languages: 'de,de_CH,en,fr_CH'
        fallback_languages:
          de: ''
          de_CH: ''
          en: ''
          fr_CH: ''
        default_language: en
        debug_admin_translations: false
```

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

### Document Types

Example: Up

```php 
$docTypesMigrationHelper = $this->getDocTypesMigrationHelper();
$docTypesMigrationHelper->create('doktype', 'controller');
$docTypesMigrationHelper->update('doktype', 'newDoctype', 'newcontroller');
```

Example: Down

```php
$docTypesMigrationHelper = $this->getDocTypesMigrationHelper();
$docTypesMigrationHelper->delete('newDoctype');
```

### Bundle / Extension

It is not possible to enable and install one bundle in one migration!

You need to make two migrations one with enable (disable) and one with install (uninstall) and then run it with the
command
[Migrate in separate process](#migrate-in-separate-process). Otherwise it would not find the newly enabled bundle for
the installation.

Example: Up

```php 
$bundleMigrationHelper = $this->getBundleMigrationHelper();
$bundleMigrationHelper->enable('Basilicom\PimcorePluginMigrationToolkit\PimcorePluginMigrationToolkitBundle');
```

Example: Down

```php
$bundleMigrationHelper = $this->getBundleMigrationHelper();
$bundleMigrationHelper->disable('Basilicom\PimcorePluginMigrationToolkit\PimcorePluginMigrationToolkitBundle');
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
$objectbrickMigrationHelper = $this->getObjectbrickMigrationHelper();
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```

Example: Down

```php
$objectbrickName = 'brick';
$objectbrickMigrationHelper = $this->getObjectbrickMigrationHelper();
$objectbrickMigrationHelper->delete($objectbrickName);
// OR
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForDownMigration($className);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```

### Fieldcollection

Example: Up

```php 
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldcollectionMigrationHelper();
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$fieldcollectionMigrationHelper->createOrUpdate($key, $jsonPath);
```

Example: Down

```php
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldcollectionMigrationHelper();
$fieldcollectionMigrationHelper->delete($key);
// OR
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForDownMigration($className);
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

// typehint says it should return int, but it is string
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
// typehint says it should return int, but it is string
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

### Image Thumbnail

⚠️ this helper is deprecated as Pimxore X is storing Image Thumbnails in YAML-files.

Example: Up

```php
$imageThumbnailMigrationHelper = $this->getImageThumbnailMigrationHelper();
$thumbnail = $imageThumbnailMigrationHelper->create('thumbnail', 'description')
->addTransformationFrame(40, 50, true)
->removeTransformation(ImageThumbnailMigrationHelper::TRANSFORMATION_SET_BACKGROUND_COLOR)
->addTransformationSetBackgroundColor('#888888');
```

or updating an existing one:

```php
$imageThumbnailMigrationHelper = $this->getImageThumbnailMigrationHelper();
$thumbnail = $imageThumbnailMigrationHelper->getThumbnailByName('thumbnail')
->addTransformationScaleByHeight(250)
->addTransformationScaleByWidth(200);
```


Example: Down

```php
$imageThumbnailMigrationHelper = $this->getImageThumbnailMigrationHelper();
$imageThumbnailMigrationHelper->delete('thumbnail');
```

### Video Thumbnail

Example: Up

```php
$name = 'thumbnail';
$videoThumbnailMigrationHelper = $this->getVideoThumbnailMigrationHelper();
$videoThumbnailMigrationHelper->create($name, 'description');

```

Example: Down

```php
$name = 'thumbnail';
$videoThumbnailMigrationHelper = $this->getVideoThumbnailMigrationHelper();
$videoThumbnailMigrationHelper->delete($name);
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

to add the translations into the pimcore shared/admin translations.
default domain is 'messages' (shared translations).
but you can change it to any domain, also 'admin' (admin translations).

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
$this->getTranslationMigrationHelper()->addTranslations($this->translations);
```

#### Example: Down
```php
$this->getTranslationMigrationHelper()->removeTranslationsByKey(array_keys($this->translations));
```

## Commands

### Migrate in separate process

Executes the same migrations as the ```doctrine:migrations:migrate``` command, but each one is run in a separate process,
to prevent problems with PHP classes that changed during the runtime.

```shell 
bin/console basilicom:migrations:migrate-in-separate-processes
```

You're also able to migrate only specific bundles using the bundle prefix.

```shell 
bin/console basilicom:migrations:migrate-in-separate-processes --bundle "App"
bin/console basilicom:migrations:migrate-in-separate-processes --bundle "Pimcore"

bin/console basilicom:migrations:migrate-in-separate-processes -b "App"
bin/console basilicom:migrations:migrate-in-separate-processes -b "Pimcore"
```

In some cases you might run migrations on large datasets. Therefor 120s of timeout per migration won't be enough. 
To adapt the timeout just pass the `--timeout` option. To unset the timeout at all, pass `0`.

```shell 
bin/console basilicom:migrations:migrate-in-separate-processes --timeout 0
bin/console basilicom:migrations:migrate-in-separate-processes --timeout 180
```

```shell 
bin/console basilicom:migrations:migrate-in-separate-processes -t 0
bin/console basilicom:migrations:migrate-in-separate-processes -t 180
```

### Import Translations

To import a csv file, like the exported shared translations from pimcore. To Pimcore shared translations. Or to Pimcore
admin translations.

```shell 
# examples
bin/console basilicom:import:translations /path/to/project/translations/shared-translations.csv
bin/console basilicom:import:translations /path/to/project/translations/shared-translations.csv --replaceExistingTranslation
bin/console basilicom:import:translations /path/to/project/translations/admin-translations.csv --replaceExistingTranslation --admin
```

## Ideas

* command: ```basilicom:migrations:generate <which type of migration>```
    * types e.g:
        * general migration for extended class only
        * class migration template with folders
        * ...
* enhance command: ```basilicom:migrations:migrate-in-separate-processes```
    * to also revert ```prev``` or ```<versionnumber>```
