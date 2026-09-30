<?php

declare(strict_types=1);

/**
 * Installs Pimcore into tests/App for the test rig.
 *
 * The CLI installer insists on a signed product key, which a throwaway rig does not have,
 * so this script drives the Installer service directly and afterwards recreates the
 * needs-install marker: with an empty encryption secret and that marker present the
 * kernel skips the product registration check.
 */

use Basilicom\PimcorePluginMigrationToolkit\Tests\App\Kernel;
use Pimcore\Bootstrap;
use Pimcore\Bundle\InstallBundle\Installer;
use Pimcore\Bundle\InstallBundle\InstallerKernel;
use Pimcore\Config;
use Pimcore\Console\Style\PimcoreStyle;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

require dirname(__DIR__) . '/vendor/autoload.php';

define('PIMCORE_PROJECT_ROOT', dirname(__DIR__) . '/tests/App');
$_SERVER['PIMCORE_KERNEL_CLASS'] = $_ENV['PIMCORE_KERNEL_CLASS'] = Kernel::class;
// Installer::runInstall() instantiates \App\Kernel by name.
class_alias(Kernel::class, 'App\Kernel');

Bootstrap::$isInstaller = true;
Bootstrap::bootstrap();

$marker = PIMCORE_PROJECT_ROOT . '/var/config/needs-install.lock';
if (!is_dir(dirname($marker))) {
    mkdir(dirname($marker), 0777, true);
}
touch($marker);

// The Installer is private in the installer container; only the CLI command may use it.
$kernel = new class (PIMCORE_PROJECT_ROOT, Config::getEnvironment(), true) extends InstallerKernel {
    protected function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new class () implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                $container->getDefinition(Installer::class)->setPublic(true);
            }
        });
    }
};
Pimcore::setKernel($kernel);
$kernel->boot();

$installer = $kernel->getContainer()->get(Installer::class);
$installer->setSkipProductRegistrationConfig(true);
$installer->setSkipDatabaseConfig(true);
$installer->setCommandLineOutput(new PimcoreStyle(new ArgvInput(), new ConsoleOutput()));

$errors = $installer->install([
    'mysql_host_socket'   => $_SERVER['MYSQL_HOST'],
    'mysql_port'          => (int) $_SERVER['MYSQL_PORT'],
    'mysql_username'      => $_SERVER['MYSQL_USER'],
    'mysql_password'      => $_SERVER['MYSQL_PASSWORD'],
    'mysql_database'      => $_SERVER['MYSQL_DATABASE'],
    'mysql_ssl_cert_path' => '',
    'admin_username'      => 'admin',
    'admin_password'      => 'admin-test-1234',
    'encryption_secret'   => null,
    'instance_identifier' => null,
    'product_key'         => '',
]);

touch($marker);

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, $errors) . PHP_EOL);
    exit(1);
}

echo 'Pimcore installed into ' . PIMCORE_PROJECT_ROOT . PHP_EOL;
