<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\Asset;
use Pimcore\Model\Asset\Folder;
use Pimcore\Model\Asset\Service as AssetService;
use Pimcore\Model\Element\Service;

class AssetMigrationHelper extends AbstractMigrationHelper
{
    /**
     * @throws Exception
     */
    public function createAsset(string $dataSource, string $targetFolderPath, ?string $assetKey = null): Asset
    {
        if (file_exists($dataSource) === false || is_readable($dataSource) === false) {
            throw new Exception('could not find or read file for asset creation');
        }

        /** @var Asset\Folder $targetFolder */
        $targetFolder = AssetService::createFolderByPath($targetFolderPath);

        $fileinfo = pathinfo($dataSource);
        $key = Service::getValidKey($assetKey ?? $fileinfo['basename'], 'asset');

        $fullPath = $targetFolder->getFullPath() . '/' . $key;
        if (Asset::getByPath($fullPath, true) instanceof Asset) {
            $message = sprintf('The Asset "%s" could not be created, because already exists at "%s".', $key, $fullPath);
            throw new InvalidSettingException($message);
        }

        $asset = new Asset();
        $asset->setKey($key);
        $asset->setParent($targetFolder);
        $asset->setData(file_get_contents($dataSource));
        $asset->save();

        return $asset;
    }

    /**
     * @throws Exception
     */
    public function updateAsset(Asset $asset, string $dataSource, ?string $newAssetName = null): void
    {
        if (file_exists($dataSource) === false || is_readable($dataSource) === false) {
            throw new Exception('could not find or read file for asset creation');
        }

        $fileinfo = pathinfo($dataSource);
        $key = Service::getValidKey($newAssetName ?? $fileinfo['basename'], 'asset');

        $asset->setKey($key);
        $asset->setData(file_get_contents($dataSource));
        $asset->save();
    }

    /**
     * @throws InvalidSettingException
     */
    public function createFolderByParentId(string $name, int $parentId): void
    {
        $parent = Folder::getById($parentId);

        if (empty($parent)) {
            $message = sprintf(
                'The Folder "%s" could not be created, because the parent does not exist',
                $name
            );

            throw new InvalidSettingException($message);
        }

        $intendedPath = $parent->getRealFullPath() . '/' . $name;

        $this->createFolderByPath($intendedPath);
    }

    /**
     * @throws InvalidSettingException
     */
    public function createFolderByPath(string $path): void
    {
        try {
            AssetService::createFolderByPath($path);
        } catch (Exception $exception) {
            $message = sprintf('The Folder "%s" could not be created.', $path);

            throw new InvalidSettingException($message);
        }
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteById(int $id): void
    {
        if ($id === 1) {
            throw new InvalidSettingException('You cannot delete the root asset.');
        }

        $asset = Asset::getById($id);

        if (empty($asset)) {
            $message = sprintf('Asset with id "%s" can not be deleted, because it does not exist.', $id);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $asset->delete();
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteByPath(string $path): void
    {
        if (empty($path)) {
            throw new InvalidSettingException('Asset can not be deleted, because path needs to be defined');
        }

        $asset = Asset::getByPath($path);

        if (empty($asset)) {
            $message = sprintf('Asset with path "%s" can not be deleted, because it does not exist.', $path);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $asset->delete();
    }
}
