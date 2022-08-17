# Pimcore Plugin Migration Toolkit

## Why?
In every project we have migrations for the same things.
Like System Settings, Classes, etc.

This plugin provides you with the migration helpers and further tools.

## Usage Migration Helpers

For all migrations extend them from the class ```AbstractAdvancedPimcoreMigration```.

### Migration Data
If a migration needs data it needs to be located in the following folder:
```/project/app/Migrations/data/<classname-of-the-migration>```


### System Settings
Example: Up
```
$systemSettingsMigrationHelper = $this->getSystemSettingsMigrationHelper();
$systemSettingsMigrationHelper->setAdminColor('#003366');
$systemSettingsMigrationHelper->setLoginColor('#003366');
$systemSettingsMigrationHelper->setInvertColorsForLoginScreen(true);
$systemSettingsMigrationHelper->setHideEditImageTab(true);
```
Example: Down
```
$systemSettingsMigrationHelper = $this->getSystemSettingsMigrationHelper();
$systemSettingsMigrationHelper->removeAdminColor();
$systemSettingsMigrationHelper->removeLoginColor();
$systemSettingsMigrationHelper->removeInvertColorsForLoginScreen();
$systemSettingsMigrationHelper->removeHideEditImageTab();
```

### Language Settings
Example: Up
``` 
$languageSettingsMigrationHelper = $this->getLanguageSettingsMigrationHelper();
$languageSettingsMigrationHelper->setDefaultLanguageInAdminInterface('de');
$languageSettingsMigrationHelper->addLanguageWithFallback('de', 'en');
$languageSettingsMigrationHelper->setDefaultLanguage('de');
```
Example: Down
```
$languageSettingsMigrationHelper = $this->getLanguageSettingsMigrationHelper();
$languageSettingsMigrationHelper->setDefaultLanguageInAdminInterface('en');
$languageSettingsMigrationHelper->removeLanguage('de');
$languageSettingsMigrationHelper->setDefaultLanguage('en');
```

### Website Settings
Example: Up
``` 
$websiteSettingsMigrationHelper = $this->getWebsiteSettingsMigrationHelper();
$websiteSettingsMigrationHelper->createOfTypeText('text', 'text hier');
$websiteSettingsMigrationHelper->createOfTypeDocument('document', 1);
$websiteSettingsMigrationHelper->createOfTypeAsset('asset', 1);
$websiteSettingsMigrationHelper->createOfTypeObject('object', 1);
$websiteSettingsMigrationHelper->createOfTypeBool('bool', false);
```
Example: Down
```
$websiteSettingsMigrationHelper = $this->getWebsiteSettingsMigrationHelper();
$websiteSettingsMigrationHelper->delete('text');
$websiteSettingsMigrationHelper->delete('document');
$websiteSettingsMigrationHelper->delete('asset');
$websiteSettingsMigrationHelper->delete('object');
$websiteSettingsMigrationHelper->delete('bool');
```

### Static Routes
Example: Up
``` 
$staticRoutesMigrationHelper = $this->getStaticRoutesMigrationHelper();
$staticRoutesMigrationHelper->create(
    'route',
    '/pattern',
    '/reverse',
    'controller',
    'action',
    'variable1,variable2',
    'default1,default2',
    'bundle',
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
```
$staticRoutesMigrationHelper = $this->getStaticRoutesMigrationHelper();
$staticRoutesMigrationHelper->delete('route');
$staticRoutesMigrationHelper->delete('route1');
```

### User Roles
There is no way to remove the workspaces (dataobjects, documents or assets).

Even when deleting a user role in the pimcore backend the workspace data stays in the database.

Example: Up
``` 
$userRolesMigrationHelper = $this->getUserRolesMigrationHelper();
$userRolesMigrationHelper->create(
    'migrationRole',
    ['dashboards', 'admin_translations'],
    ['doctype'],
    ['class'],
    ['de', 'en'],
    ['de']
);
$userRolesMigrationHelper->addWorkspaceDataObject($role,$path,true,true,false,true,false,true,true,true,true,true,true);
$userRolesMigrationHelper->addWorkspaceDocument($role,$path,true,true,false,true,false,true,true,true,true,true,true);
$userRolesMigrationHelper->addWorkspaceAsset($role,$path,true,true,false,true,false,true,true,true,true);
```
Example: Down
```
$userRolesMigrationHelper = $this->getUserRolesMigrationHelper();
$userRolesMigrationHelper->delete('migrationRole');
```

### Document Types
Example: Up
``` 
$docTypesMigrationHelper = $this->getDocTypesMigrationHelper();
$docTypesMigrationHelper->create('doktype', 'controller');
$docTypesMigrationHelper->update('doktype', 'newDoctype', 'newcontroller');
```
Example: Down
```
$docTypesMigrationHelper = $this->getDocTypesMigrationHelper();
$docTypesMigrationHelper->delete('newDoctype');
```

### Bundle / Extension
It is not possible to enable and install one bundle in one migration!

You need to make two migrations one with enable (disable) and one with install (uninstall) and then run it with the command 
[Migrate in separate process](#migrate-in-separate-process). Otherwise it would not find the newly enabled bundle for the installation.

Example: Up
``` 
$bundleMigrationHelper = $this->getBundleMigrationHelper();
$bundleMigrationHelper->enable('Basilicom\PimcorePluginMigrationToolkit\PimcorePluginMigrationToolkitBundle');
```
Example: Down
```
$bundleMigrationHelper = $this->getBundleMigrationHelper();
$bundleMigrationHelper->disable('Basilicom\PimcorePluginMigrationToolkit\PimcorePluginMigrationToolkitBundle');
```

### Class Definitions
Example: Up
``` 
$className = 'testing';
$classDefinitionMigrationHelper = $this->getClassDefinitionMigrationHelper();
$jsonPath = $classDefinitionMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$classDefinitionMigrationHelper->createOrUpdate($className, $jsonPath);
```
Example: Down
```
$className = 'testing';
$classDefinitionMigrationHelper = $this->getClassDefinitionMigrationHelper();
$classDefinitionMigrationHelper->delete($className);
// OR
$jsonPath = $classDefinitionMigrationHelper->getJsonDefinitionPathForDownMigration($className);
$classDefinitionMigrationHelper->createOrUpdate($className, $jsonPath);
```

### Objectbricks
Example: Up
``` 
$objectbrickName = 'brick';
$objectbrickMigrationHelper = $this->getObjectbrickMigrationHelper();
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```
Example: Down
```
$objectbrickName = 'brick';
$objectbrickMigrationHelper = $this->getObjectbrickMigrationHelper();
$objectbrickMigrationHelper->delete($objectbrickName);
// OR
$jsonPath = $objectbrickMigrationHelper->getJsonDefinitionPathForDownMigration($className);
$objectbrickMigrationHelper->createOrUpdate($objectbrickName, $jsonPath);
```

### Fieldcollection
Example: Up
``` 
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldcollectionMigrationHelper();
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForUpMigration($className);
$fieldcollectionMigrationHelper->createOrUpdate($key, $jsonPath);
```
Example: Down
```
$key = 'test';
$fieldcollectionMigrationHelper = $this->getFieldcollectionMigrationHelper();
$fieldcollectionMigrationHelper->delete($key);
// OR
$jsonPath = $fieldcollectionMigrationHelper->getJsonDefinitionPathForDownMigration($className);
$fieldcollectionMigrationHelper->createOrUpdate($key, $jsonPath);
```

### Custom Layouts
Custom Layouts will get the id like "lower(<classId><name>)".
``` 
const CUSTOM_LAYOUT = [
    'classId' => 'reference',
    'name' => 'readOnly'
];
``` 
Example: Up
``` 
$customLayoutMigrationHelper = $this->getCustomLayoutMigrationHelper();
$jsonPath = $customLayoutMigrationHelper->getJsonDefinitionPathForUpMigration(self::CUSTOM_LAYOUT['name'], self::CUSTOM_LAYOUT['classId']);
$customLayoutMigrationHelper->createOrUpdate(
    self::CUSTOM_LAYOUT['name'],
    self::CUSTOM_LAYOUT['classId'],
    $jsonPath
);
```
Example: Down
```
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
``` 
const PAGE = [
    'key' => 'diga',
    'name' => 'DiGA',
    'controller' => 'Search',
    'parentPath' => '/',
];
``` 
Example: Up
``` 
$documentMigrationHelper = $this->getDocumentMigrationHelper();
$documentMigrationHelper->createPageByParentPath(
    self::PAGE['key'],
    self::PAGE['name'],
    self::PAGE['controller'],
    self::PAGE['parentPath']
);
```
Example: Down
```
$documentMigrationHelper = $this->getDocumentMigrationHelper();
$documentMigrationHelper->deleteByPath(
    self::PAGE['parentPath'].self::PAGE['key']
);
```

### Object (Folder)
Example: Up
``` 
$dataObjectMigrationHelper = $this->getDataObjectMigrationHelper();
$dataObjectMigrationHelper->createFolderByParentId('folder1', 1);
$dataObjectMigrationHelper->createFolderByPath('/folder2/subfolder');
```
Example: Down
```
$dataObjectMigrationHelper = $this->getDataObjectMigrationHelper();
$dataObjectMigrationHelper->deleteById(2);
$dataObjectMigrationHelper->deleteByPath('/folder2');
```

### Asset (Folder)
Example: Up
``` 
$assetMigrationHelper = $this->getAssetMigrationHelper();
$assetMigrationHelper->createFolderByParentId('name', 1);
$assetMigrationHelper->createFolderByPath('/asset1/subasset');
```
Example: Down
```
$assetMigrationHelper = $this->getAssetMigrationHelper();
$assetMigrationHelper->deleteById(2);
$assetMigrationHelper->deleteByPath('/asset1');
```

### Image Thumbnail
Example: Up
``` 
$name = 'thumbnail';
$imageThumbnailMigrationHelper = $this->getImageThumbnailMigrationHelper();
$imageThumbnailMigrationHelper->create($name, 'description');
$imageThumbnailMigrationHelper->addTransformationFrame($name, 40, 50, true);
$imageThumbnailMigrationHelper->removeTransformation($name, ImageThumbnailMigrationHelper::TRANSFORMATION_SET_BACKGROUND_COLOR);
$imageThumbnailMigrationHelper->addTransformationSetBackgroundColor($name, '#888888');
```
Example: Down
```
$name = 'thumbnail';
$imageThumbnailMigrationHelper = $this->getImageThumbnailMigrationHelper();
$imageThumbnailMigrationHelper->delete($name);
```

### Video Thumbnail
Example: Up
``` 
$name = 'thumbnail';
$videoThumbnailMigrationHelper = $this->getVideoThumbnailMigrationHelper();
$videoThumbnailMigrationHelper->create($name, 'description');

```
Example: Down
```
$name = 'thumbnail';
$videoThumbnailMigrationHelper = $this->getVideoThumbnailMigrationHelper();
$videoThumbnailMigrationHelper->delete($name);
```

### QuantityValue Unit
Example: Up
``` 
$quantityValueUnitMigrationHelper = $this->getQuantityValueUnitMigrationHelper();
$quantityValueUnitMigrationHelper->createOrUpdate('abr', 'Long Abbreviation');
```
Example: Down
```
$quantityValueUnitMigrationHelper = $this->getQuantityValueUnitMigrationHelper();
$quantityValueUnitMigrationHelper->delete('abr');
```

## Commands
### Migrate in separate process
Executes the same migrations as the ```pimcore:migrations:migrate``` command,
but each one is run in a separate process, to prevent problems with PHP classes that changed during the runtime.
``` 
bin/console basilicom:migrations:migrate-in-separate-processes
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
To import a csv file, like the exported shared translations from pimcore.
To Pimcore shared translations.
Or to Pimcore admin translations.
``` 
# examples
bin/console basilicom:import:translations /path/to/shared-translations.csv
bin/console basilicom:import:translations /path/to/shared-translations.csv --replaceExistingTranslation
bin/console basilicom:import:translations /path/to/admin-translations.csv --replaceExistingTranslation --admin
```

## Ideas
* command: ```basilicom:migrations:generate <which type of migration>```
    * types e.g:
        * general migration for extended class only
        * class migration template with folders
        * ...
* enhance command: ```basilicom:migrations:migrate-in-separate-processes```
    * to also revert ```prev``` or ```<versionnumber>```
* Video Thumbnail Migration Helper
