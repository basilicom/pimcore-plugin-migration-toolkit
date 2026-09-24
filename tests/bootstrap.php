<?php

declare(strict_types=1);

use Basilicom\PimcorePluginMigrationToolkit\Tests\App\Kernel;
use Pimcore\Bootstrap;

require dirname(__DIR__) . '/vendor/autoload.php';

define('PIMCORE_CONSOLE', true);
define('PIMCORE_PROJECT_ROOT', __DIR__ . '/App');
$_SERVER['PIMCORE_KERNEL_CLASS'] = $_ENV['PIMCORE_KERNEL_CLASS'] = Kernel::class;

Bootstrap::setProjectRoot();
Bootstrap::bootstrap();
Bootstrap::startupCli();
