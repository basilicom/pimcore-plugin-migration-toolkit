<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\AssetMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Exception;
use Pimcore\Model\Asset;

class AssetMigrationHelperTest extends AbstractFunctionalTestCase
{
    private AssetMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new AssetMigrationHelper());
    }

    public function testCreateAssetUploadsTheFileIntoTheFolder(): void
    {
        $folder = $this->folderPath();
        $source = $this->sourceFile('first content');

        $asset = $this->helper->createAsset($source, $folder, 'my-file.txt');

        self::assertSame($folder . '/my-file.txt', $asset->getFullPath());
        self::assertSame('first content', Asset::getByPath($folder . '/my-file.txt')?->getData());
    }

    public function testCreateAssetDefaultsToTheSourceFilenameAndRejectsDuplicates(): void
    {
        $folder = $this->folderPath();
        $source = $this->sourceFile('content');

        $asset = $this->helper->createAsset($source, $folder);
        self::assertSame(basename($source), $asset->getKey());

        $this->expectException(InvalidSettingException::class);
        $this->helper->createAsset($source, $folder);
    }

    public function testCreateAssetRejectsMissingSources(): void
    {
        $this->expectException(Exception::class);

        $this->helper->createAsset(sys_get_temp_dir() . '/missing-' . uniqid(), $this->folderPath());
    }

    public function testUpdateAssetReplacesDataAndKey(): void
    {
        $folder = $this->folderPath();
        $asset  = $this->helper->createAsset($this->sourceFile('old'), $folder, 'old.txt');

        $this->helper->updateAsset($asset, $this->sourceFile('new'), 'new.txt');

        self::assertNull(Asset::getByPath($folder . '/old.txt'));
        self::assertSame('new', Asset::getByPath($folder . '/new.txt')?->getData());
    }

    public function testCreateFolderByPathAndByParentId(): void
    {
        $path = $this->folderPath();

        $this->helper->createFolderByPath($path . '/sub');
        self::assertInstanceOf(Asset\Folder::class, Asset::getByPath($path . '/sub'));

        $other = $this->folderPath();
        $this->helper->createFolderByParentId(ltrim($other, '/'), 1);
        self::assertInstanceOf(Asset\Folder::class, Asset::getByPath($other));
    }

    public function testCreateFolderByParentIdRejectsUnknownParents(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createFolderByParentId('orphan', 987654321);
    }

    public function testDeleteByIdAndByPath(): void
    {
        $path = $this->folderPath();
        $this->helper->createFolderByPath($path . '/a');
        $this->helper->createFolderByPath($path . '/b');
        $idA = Asset::getByPath($path . '/a')?->getId();
        self::assertNotNull($idA);

        $this->helper->deleteById($idA);
        $this->helper->deleteByPath($path . '/b');

        self::assertNull(Asset::getById($idA));
        self::assertNull(Asset::getByPath($path . '/b'));
    }

    public function testRootAndEmptyPathAreProtected(): void
    {
        try {
            $this->helper->deleteById(1);
            self::fail('root must not be deletable');
        } catch (InvalidSettingException) {
        }

        $this->expectException(InvalidSettingException::class);
        $this->helper->deleteByPath('');
    }

    public function testDeleteOfMissingAssetIsReported(): void
    {
        $this->helper->deleteByPath('/' . $this->uniqueName('toolkit-missing-'));
        $this->helper->deleteById(987654321);

        self::assertCount(2, $this->messages);
        $this->assertMessageContains('does not exist');
    }

    private function folderPath(): string
    {
        $path = '/' . $this->uniqueName('toolkit-assets-');
        $this->onTearDown(static fn () => Asset::getByPath($path)?->delete());

        return $path;
    }

    private function sourceFile(string $content): string
    {
        $file = sys_get_temp_dir() . '/' . $this->uniqueName('toolkit-source-') . '.txt';
        file_put_contents($file, $content);
        $this->onTearDown(static fn () => @unlink($file));

        return $file;
    }
}
