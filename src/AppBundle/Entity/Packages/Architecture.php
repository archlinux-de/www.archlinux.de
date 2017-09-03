<?php

namespace AppBundle\Entity\Packages;

// @TODO as embeddable entity?
class Architecture
{
    public const X86_64 = 'x86_64';
    public const I686 = 'i686';
    public const ANY = 'any';

    /**
     * @param string $a
     * @param string $b
     * @return bool
     */
    public static function isCompatible(string $a, string $b): bool
    {
        return ($a === self::ANY || $b === self::ANY) || $a === $b;
    }
}
