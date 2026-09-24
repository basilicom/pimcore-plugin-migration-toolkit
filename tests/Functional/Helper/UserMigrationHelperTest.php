<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Helper\UserMigrationHelper;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore\Model\User;

class UserMigrationHelperTest extends AbstractFunctionalTestCase
{
    private UserMigrationHelper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = $this->withOutput(new UserMigrationHelper());
    }

    public function testCreateBuildsTheLoginFromTheName(): void
    {
        $surname = $this->surname();

        $user = $this->helper->create('Toolkit', $surname, ' toolkit@example.com ', true, false);

        self::assertSame('toolkit.' . strtolower($surname), $user->getName());
        self::assertSame('Toolkit', $user->getFirstname());
        self::assertSame('toolkit@example.com', $user->getEmail());
        self::assertTrue($user->isAdmin());
        self::assertFalse($user->getActive());
        self::assertSame($user->getId(), User::getByName('toolkit.' . strtolower($surname))?->getId());
    }

    public function testSurnameSpacesBecomeDashes(): void
    {
        $surname = $this->surname() . ' Two';

        $user = $this->helper->create('Toolkit', $surname, 'x@example.com', false);

        self::assertSame('toolkit.' . strtolower(str_replace(' ', '-', $surname)), $user->getName());
    }

    public function testCreateReturnsTheExistingUser(): void
    {
        $surname = $this->surname();
        $first   = $this->helper->create('Toolkit', $surname, 'x@example.com', false);

        $second = $this->helper->create('Toolkit', $surname, 'other@example.com', true);

        self::assertSame($first->getId(), $second->getId());
        self::assertSame('x@example.com', $second->getEmail());
        $this->assertMessageContains('already exists');
    }

    public function testDelete(): void
    {
        $surname = $this->surname();
        $this->helper->create('Toolkit', $surname, 'x@example.com', false);

        $this->helper->delete('Toolkit', $surname);

        self::assertNull(User::getByName('toolkit.' . strtolower($surname)));
    }

    public function testDeleteOfMissingUserIsReported(): void
    {
        $this->helper->delete('Toolkit', $this->uniqueName('Missing'));

        $this->assertMessageContains('does not exist');
    }

    private function surname(): string
    {
        $surname = $this->uniqueName('Tester');
        $this->onTearDown(static function () use ($surname): void {
            foreach ([$surname, $surname . ' Two'] as $candidate) {
                User::getByName('toolkit.' . strtolower(str_replace(' ', '-', $candidate)))?->delete();
            }
        });

        return $surname;
    }
}
