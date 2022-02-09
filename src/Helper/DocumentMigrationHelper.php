<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Model\Document;
use Pimcore\Model\Document\Page;
use Pimcore\Model\Document\Service as DocumentService;
use Pimcore\Model\Element\Service;
use Pimcore\Model\Property\Predefined as PredefinedProperty;

class DocumentMigrationHelper extends AbstractMigrationHelper
{
    const TYPE_PAGE = 'page';
    const TYPE_EMAIL = 'email';
    const VALID_DOCUMENT_TYPES = [self::TYPE_EMAIL, self::TYPE_PAGE];

    const EMAIl_PROP_SUBJECT = 'subject';
    const EMAIl_PROP_FROM = 'from';
    const EMAIl_PROP_REPLY_TO = 'replyTo';
    const EMAIl_PROP_TO = 'to';
    const EMAIl_PROP_CC = 'cc';
    const EMAIl_PROP_BCC = 'bcc';
    const EMAIL_PROPS = [self::EMAIl_PROP_SUBJECT, self::EMAIl_PROP_FROM, self::EMAIl_PROP_REPLY_TO, self::EMAIl_PROP_TO, self::EMAIl_PROP_CC, self::EMAIl_PROP_BCC];

    private bool $shouldPublish = false;

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function createPageByParentId(
        string $key,
        string $name,
        string $controller,
        int $parentId
    ): Page
    {
        $parent = Document::getById($parentId);

        return $this->create($parent, $name, $key, $controller, self::TYPE_PAGE);
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function createPageByParentPath(
        string $key,
        string $name,
        string $controller,
        string $parentPath
    ): Page
    {
        $parent = Document::getByPath($parentPath);

        return $this->create($parent, $name, $key, $controller, self::TYPE_PAGE);
    }

    public function createEmailByPath(
        string $key,
        string $controller,
        string $parentPath,
        ?string $subject = null,
        ?string $from = null,
        ?string $replyTo = null,
        ?string $to = null,
        ?string $cc = null,
        ?string $bcc = null
    )
    {
        $parent = Document::getByPath($parentPath);

        $emailDetails = [];
        if ($subject) {
            $emailDetails[self::EMAIl_PROP_SUBJECT] = $subject;
        }
        if ($from) {
            $emailDetails[self::EMAIl_PROP_FROM] = $from;
        }
        if ($replyTo) {
            $emailDetails[self::EMAIl_PROP_REPLY_TO] = $replyTo;
        }
        if ($to) {
            $emailDetails[self::EMAIl_PROP_TO] = $to;
        }
        if ($cc) {
            $emailDetails[self::EMAIl_PROP_CC] = $cc;
        }
        if ($bcc) {
            $emailDetails[self::EMAIl_PROP_BCC] = $bcc;
        }

        return $this->create($parent, $key, $key, $controller, self::TYPE_EMAIL, $emailDetails);
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     * @see \Pimcore\Bundle\AdminBundle\Controller\Admin\Document\DocumentController::addAction()
     *
     */
    private function create(
        ?Document $parent,
        string $name,
        string $key,
        string $controller,
        string $type,
        array $emailDetails = [],
    ): Document
    {
        if (in_array($type, self::VALID_DOCUMENT_TYPES) === false) {
            $message = sprintf('Unsupported type "%s".', $type);
            throw new InvalidSettingException($message);
        }

        if (empty($parent)) {
            $message = sprintf(
                'The Document "%s" (%s) could not be created, because the parent was not found.',
                $name,
                $key
            );
            throw new InvalidSettingException($message);
        }

        $intendedPath = $parent->getRealFullPath() . '/' . $key;
        if (DocumentService::pathExists($intendedPath)) {
            $message = sprintf(
                'The Document "%s" (%s) could not be created, because already exists with path "%s".',
                $name,
                $key,
                $intendedPath
            );
            throw new InvalidSettingException($message);
        }

        $createValues = [
            'userOwner'        => 0,
            'userModification' => 0,
            'published'        => false,
            'key'              => Service::getValidKey($key, 'document'),
            'controller'       => $controller,
        ];

        if ($type === self::TYPE_PAGE) {
            $doc = Page::create($parent->getId(), $createValues, false);
            $doc->setTitle($name);
            $doc->setProperty('navigation_name', 'text', $name, false, false);
        } elseif ($type === self::TYPE_EMAIL) {
            $doc = Document\Email::create($parent->getId(), $createValues, false);

            foreach (self::EMAIL_PROPS as $emailProp) {
                if (isset($emailDetails[$emailProp]) === true) {
                    $setter = 'set' . ucfirst($emailProp);
                    $doc->$setter($emailDetails[$emailProp]);
                }
            }
        }

        $doc->setPublished($this->shouldPublish());
        $doc->save();

        return $doc;
    }

    public function createFolderByPath(string $path)
    {
        return Document\Service::createFolderByPath($path);
    }

    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function deleteById(int $id): void
    {
        if ($id === 1) {
            throw new InvalidSettingException('You cannot delete the root document.');
        }

        $document = Document::getById($id);
        if (empty($document)) {
            $message = sprintf('Document with id "%s" can not be deleted, because it does not exist.', $id);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $document->delete();
    }

    /**
     * @throws InvalidSettingException
     */
    public function deleteByPath(string $path): void
    {
        if (empty($path)) {
            throw new InvalidSettingException('Document can not be deleted, because path needs to be defined');
        }

        $document = Document::getByPath($path);

        if (empty($document)) {
            $message = sprintf('Document with path "%s" can not be deleted, because it does not exist.', $path);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $document->delete();
    }

    public function createOrUpdatePredefinedProperty(
        string $key,
        string $name,
        string $description,
        string $type,
        string $contentType,
        bool $isInheritable = true
    ): PredefinedProperty
    {
        try {
            $property = PredefinedProperty::getByKey($key);
        } catch (Exception $e) {
            $property = null;
        }

        if (!$property) {
            $property = PredefinedProperty::create();
            $property->setKey($key);
        }

        $property->setName($name);
        $property->setDescription($description);
        $property->setType($type);
        $property->setCtype($contentType);
        $property->setInheritable($isInheritable);
        $property->save();

        return $property;
    }

    public function removePredefinedProperty(string $key): void
    {
        $property = PredefinedProperty::getByKey($key);
        if ($property) {
            $property->delete();
        }
    }

    public function shouldPublish(): bool
    {
        return $this->shouldPublish;
    }

    public function setShouldPublish(bool $shouldPublish): void
    {
        $this->shouldPublish = $shouldPublish;
    }
}
