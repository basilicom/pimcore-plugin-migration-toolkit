<?php

declare(strict_types=1);

namespace Basilicom\PimcorePluginMigrationToolkit\Helper;

use Basilicom\PimcorePluginMigrationToolkit\Exceptions\InvalidSettingException;
use Pimcore\Model\DataObject\QuantityValue\Unit as QuantityValueUnit;

class QuantityValueUnitMigrationHelper extends AbstractMigrationHelper
{
    /** @throws InvalidSettingException */
    public function createOrUpdate(
        string $id,
        string $abbreviation,
        string $longname,
        ?QuantityValueUnit $baseunit = null,
        ?float $factor = null,
        ?float $conversionOffset = null,
        string $converter = ''
    ): void {
        if (preg_match('/^[[:alnum:]]+$/', $id) === 0) {
            throw new InvalidSettingException('The id "' . $id . '" should be only contain "a-zA-Z0-9".');
        }

        $unit = QuantityValueUnit::getById($id);

        if (empty($unit)) {
            $unit = new QuantityValueUnit();
        }

        $unit->setId($id);
        $unit->setAbbreviation($abbreviation);
        $unit->setLongname($longname);

        if (!empty($baseunit)) {
            $unit->setBaseunit($baseunit);
        }
        if (!empty($factor)) {
            $unit->setFactor($factor);
        }
        if (!empty($conversionOffset)) {
            $unit->setConversionOffset($conversionOffset);
        }
        if (!empty($converter)) {
            $unit->setConverter($converter);
        }

        $unit->save();
    }

    public function delete(string $id): void
    {
        $unit = QuantityValueUnit::getById($id);

        if (empty($unit)) {
            $message = sprintf(
                'Quantity Value Unit with id "%s" can not be deleted, because it does not exist.',
                $id
            );
            $this->getOutput()->writeMessage($message);

            return;
        }

        $unit->delete();

        $this->forgetRuntimeCache();
    }
}
