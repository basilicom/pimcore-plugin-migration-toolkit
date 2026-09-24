<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\Command;

use Basilicom\PimcorePluginMigrationToolkit\Command\SyncTranslationsCommand;
use Basilicom\PimcorePluginMigrationToolkit\Tests\Functional\AbstractFunctionalTestCase;
use Pimcore;
use Pimcore\Model\Translation;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class SyncTranslationsCommandTest extends AbstractFunctionalTestCase
{
    private string $catalogueDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->catalogueDir = sys_get_temp_dir() . '/toolkit-sync-' . uniqid('', true);
        (new Filesystem())->mkdir($this->catalogueDir);
        $this->onTearDown(fn () => (new Filesystem())->remove($this->catalogueDir));
    }

    public function testFlattensNestedKeysIntoTheSharedTranslations(): void
    {
        $prefix = $this->prefix();
        $this->catalogue('messages', 'en', [$prefix => ['nav' => ['home' => 'Home'], 'link.label' => 'Link']]);
        $this->catalogue('messages', 'de', [$prefix => ['nav' => ['home' => 'Start']]]);

        $tester = $this->runCommand();

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertSame('Home', Translation::getByKey($prefix . '.nav.home')?->getTranslation('en'));
        self::assertSame('Start', Translation::getByKey($prefix . '.nav.home')?->getTranslation('de'));
        self::assertSame('Link', Translation::getByKey($prefix . '.link.label')?->getTranslation('en'));
    }

    public function testKeepsEditorValuesUnlessOverwriteIsRequested(): void
    {
        $prefix = $this->prefix();
        $key    = $prefix . '.existing';
        $seed   = Translation::getByKey($key, Translation::DOMAIN_DEFAULT, true);
        $seed?->addTranslation('en', 'Editor');
        $seed?->save();
        $this->catalogue('messages', 'en', [$prefix => ['existing' => 'Catalogue']]);

        $this->runCommand();
        self::assertSame('Editor', Translation::getByKey($key)?->getTranslation('en'));

        $tester = $this->runCommand(['--overwrite' => 'always']);
        self::assertSame('Catalogue', Translation::getByKey($key)?->getTranslation('en'));
        self::assertStringContainsString('replaced 1 label', $tester->getDisplay());
    }

    public function testIsIdempotent(): void
    {
        $prefix = $this->prefix();
        $this->catalogue('messages', 'en', [$prefix => ['once' => 'Value']]);

        $first  = $this->runCommand();
        $second = $this->runCommand();

        self::assertStringContainsString('added 1 label', $first->getDisplay());
        self::assertStringContainsString('added 0 label', $second->getDisplay());
    }

    public function testDefaultsToTheConfiguredDomains(): void
    {
        $tester = $this->runCommand([], withDomain: false);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('[messages]', $tester->getDisplay());
    }

    public function testUnknownDomainFails(): void
    {
        $tester = $this->runCommand(['--domain' => ['nope' . bin2hex(random_bytes(3))]]);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
        self::assertStringContainsString('is not a registered translation domain', $tester->getDisplay());
    }

    public function testMissingCatalogueDirectoryFails(): void
    {
        $tester = $this->runCommand(['--catalogue-dir' => $this->catalogueDir . '/missing']);

        self::assertSame(Command::FAILURE, $tester->getStatusCode());
    }

    /** @param array<string, mixed> $input */
    private function runCommand(array $input = [], bool $withDomain = true): CommandTester
    {
        $command = Pimcore::getContainer()?->get(SyncTranslationsCommand::class);
        self::assertInstanceOf(SyncTranslationsCommand::class, $command);

        $tester = new CommandTester($command);
        $tester->execute([
            '--catalogue-dir' => $this->catalogueDir,
            ...($withDomain ? ['--domain' => [Translation::DOMAIN_DEFAULT]] : []),
            ...$input,
        ]);

        return $tester;
    }

    private function prefix(): string
    {
        $prefix = $this->uniqueName('toolkit-sync-');
        $this->onTearDown(static function () use ($prefix): void {
            foreach (['.nav.home', '.link.label', '.existing', '.once'] as $suffix) {
                Translation::getByKey($prefix . $suffix)?->delete();
            }
        });

        return $prefix;
    }

    /** @param array<string, mixed> $data */
    private function catalogue(string $domain, string $locale, array $data): void
    {
        file_put_contents(sprintf('%s/%s.%s.yaml', $this->catalogueDir, $domain, $locale), Yaml::dump($data, 4));
    }
}
