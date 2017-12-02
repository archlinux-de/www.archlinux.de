<?php

namespace App\Doctrine\ORM\Mapping;

use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;

class PrefixNamingStrategy extends UnderscoreNamingStrategy
{
    public function classToTableName($className)
    {
        $names = explode('\\', $className);
        array_shift($names);
        array_shift($names);
        $count = count($names);
        if ($count > 1) {
            $name = $names[$count - 2] . '_' . $names[$count - 1];
            if ($this->getCase() === CASE_UPPER) {
                return strtoupper($name);
            }

            return strtolower($name);
        }

        return parent::classToTableName($className);
    }
}
