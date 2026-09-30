<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Basilicom\PimcorePluginMigrationToolkit\Helper\DocumentMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\Document;

class DocumentMigrationHelperTest extends AbstractFunctionalTestCase
{
    private const string CONTROLLER = 'App\Controller\DefaultController::defaultAction';

    private DocumentMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new DocumentMigrationHelper());
    }

    public function testCreatePageByParentPathStaysUnpublishedByDefault(): void
    {
        $key = $this->documentKey();

        $page = $this->helper->createPageByParentPath($key, 'Page Name', self::CONTROLLER, '/');

        self::assertSame('/' . $key, $page->getFullPath());
        self::assertSame('Page Name', $page->getTitle());
        self::assertSame('Page Name', $page->getProperty('navigation_name'));
        self::assertSame(self::CONTROLLER, $page->getController());
        self::assertFalse($page->getPublished());
        self::assertFalse($this->helper->shouldPublish());
    }

    public function testCreatePageByParentIdPublishesWhenRequested(): void
    {
        $key = $this->documentKey();
        $this->helper->setShouldPublish(true);

        $page = $this->helper->createPageByParentId($key, 'Published', self::CONTROLLER, 1);

        self::assertTrue($this->helper->shouldPublish());
        self::assertTrue(Document::getById($page->getId())?->getPublished());
    }

    public function testCreateEmailByPathStoresTheHeaders(): void
    {
        $key = $this->documentKey();

        $email = $this->helper->createEmailByPath($key, self::CONTROLLER, '/', 'Subject', 'from@example.com', 'reply@example.com', 'to@example.com', 'cc@example.com', 'bcc@example.com');

        $stored = Document\Email::getByPath('/' . $key);
        self::assertSame($email->getId(), $stored?->getId());
        self::assertSame('Subject', $stored?->getSubject());
        self::assertSame('from@example.com', $stored?->getFrom());
        self::assertSame('reply@example.com', $stored?->getReplyTo());
        self::assertSame('to@example.com', $stored?->getTo());
        self::assertSame('cc@example.com', $stored?->getCc());
        self::assertSame('bcc@example.com', $stored?->getBcc());
    }

    public function testExistingPathIsRejected(): void
    {
        $key = $this->documentKey();
        $this->helper->createPageByParentPath($key, 'First', self::CONTROLLER, '/');

        $this->expectException(InvalidSettingException::class);

        $this->helper->createPageByParentPath($key, 'Second', self::CONTROLLER, '/');
    }

    public function testUnknownParentIsRejected(): void
    {
        $this->expectException(InvalidSettingException::class);

        $this->helper->createPageByParentPath($this->documentKey(), 'Orphan', self::CONTROLLER, '/' . $this->uniqueName('missing-'));
    }

    public function testCreateFolderByPath(): void
    {
        $key = $this->documentKey();

        $folder = $this->helper->createFolderByPath('/' . $key . '/sub');

        self::assertInstanceOf(Document\Folder::class, $folder);
        self::assertInstanceOf(Document\Folder::class, Document::getByPath('/' . $key));
    }

    public function testDeleteByIdAndByPath(): void
    {
        $keyA = $this->documentKey();
        $keyB = $this->documentKey();
        $idA  = $this->helper->createPageByParentPath($keyA, 'A', self::CONTROLLER, '/')->getId();
        $this->helper->createPageByParentPath($keyB, 'B', self::CONTROLLER, '/');

        $this->helper->deleteById($idA);
        $this->helper->deleteByPath('/' . $keyB);

        self::assertNull(Document::getById($idA));
        self::assertNull(Document::getByPath('/' . $keyB));
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

    public function testDeleteOfMissingDocumentIsReported(): void
    {
        $this->helper->deleteByPath('/' . $this->uniqueName('toolkit-missing-'));
        $this->helper->deleteById(987654321);

        self::assertCount(2, $this->messages);
        $this->assertMessageContains('does not exist');
    }

    private function documentKey(): string
    {
        $key = $this->uniqueName('toolkit-doc-');
        $this->onTearDown(static fn () => Document::getByPath('/' . $key)?->delete());

        return $key;
    }
}
