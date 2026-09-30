<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Tests\Unit\Translation\Reader;

use Basilicom\PimcorePluginMigrationToolkit\Translation\Exception\InvalidTranslationFileException;
use Basilicom\PimcorePluginMigrationToolkit\Translation\Reader\YamlCatalogueReader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class YamlCatalogueReaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/toolkit-catalogue-' . uniqid('', true);
        (new Filesystem())->mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->directory);
    }

    public function testMergesLocalesAndFlattensNestedKeys(): void
    {
        $this->catalogue('messages', 'en', ['nav' => ['home' => 'Home', 'link.label' => 'Link'], 'plain' => 'Plain']);
        $this->catalogue('messages', 'de', ['nav' => ['home' => 'Start']]);

        $catalogue = (new YamlCatalogueReader())->read($this->directory, 'messages');

        self::assertSame([
            'nav.home'       => ['de' => 'Start', 'en' => 'Home'],
            'nav.link.label' => ['en' => 'Link'],
            'plain'          => ['en' => 'Plain'],
        ], $catalogue);
    }

    public function testReadsOnlyTheRequestedDomainAndBothExtensions(): void
    {
        $this->catalogue('messages', 'en', ['a' => 'A']);
        $this->catalogue('admin', 'en', ['b' => 'B']);
        file_put_contents($this->directory . '/messages.de.yml', Yaml::dump(['a' => 'Ä']));
        file_put_contents($this->directory . '/messages.yaml', Yaml::dump(['ignored' => 'no locale']));

        $catalogue = (new YamlCatalogueReader())->read($this->directory, 'messages');

        self::assertSame(['a' => ['de' => 'Ä', 'en' => 'A']], $catalogue);
    }

    public function testMissingDirectoryIsRejected(): void
    {
        $this->expectException(InvalidTranslationFileException::class);

        (new YamlCatalogueReader())->read($this->directory . '/missing', 'messages');
    }

    /** @param array<string, mixed> $data */
    private function catalogue(string $domain, string $locale, array $data): void
    {
        file_put_contents(sprintf('%s/%s.%s.yaml', $this->directory, $domain, $locale), Yaml::dump($data, 4));
    }
}
