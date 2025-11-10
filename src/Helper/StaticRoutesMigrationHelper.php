<?php

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Exception;
use Pimcore\Bundle\StaticRoutesBundle\Model\Staticroute;

class StaticRoutesMigrationHelper extends AbstractMigrationHelper
{
    /**
     * @throws InvalidSettingException
     * @throws Exception
     */
    public function create(
        string $name,
        string $pattern,
        string $reverse,
        string $controller,
        ?string $variables = null,
        ?string $defaults = null,
        ?int $priority = null
    ): void {
        $route = Staticroute::getByName($name);
        if (!empty($route)) {
            $message = sprintf(
                'Not creating Static Route with name "%s". Static Route with this name already exists.',
                $name
            );

            throw new InvalidSettingException($message);
        }

        $route = new Staticroute();
        $route->setName($name);
        $route->setPattern($pattern);
        $route->setReverse($reverse);
        $route->setController($controller);

        if (!empty($variables)) {
            $route->setVariables($variables);
        }
        if (!empty($defaults)) {
            $route->setDefaults($defaults);
        }
        if (!empty($priority)) {
            $route->setPriority($priority);
        }

        $route->save();
    }

    /**
     * @throws Exception
     */
    public function delete(string $name): void
    {
        $route = Staticroute::getByName($name);

        if (empty($route)) {
            $message = sprintf('Static Route with name "%s" can not be deleted, because it does not exist.', $name);
            $this->getOutput()->writeMessage($message);

            return;
        }

        $route->delete();
    }
}
