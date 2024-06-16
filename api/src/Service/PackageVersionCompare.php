<?php

namespace App\Service;

use App\Entity\Packages\Version;
use App\Entity\Packages\VersionConstraint;

readonly class PackageVersionCompare
{
    public function __construct(private Libalpm $libalpm)
    {
    }

    public function compare(Version $version1, Version $version2): int
    {
        return $this->libalpm->alpm_pkg_vercmp(
            $version1->toShortString(),
            $version2->toShortString()
        );
    }

    public function satisfies(Version $provided, Version $requested): bool
    {
        switch ($provided->getConstraint()) {
            case VersionConstraint::EQ:
            case VersionConstraint::ANY:
                break;
            default:
                throw new \RuntimeException(
                    sprintf('Invalid version constraint for provider: %s', $provided->getConstraint()->value)
                );
        }

        $compared = $this->compare($provided, $requested);

        // https://gitlab.archlinux.org/pacman/pacman/-/blob/master/lib/libalpm/deps.c#L392
        return match ($requested->getConstraint()) {
            VersionConstraint::EQ => $compared === 0,
            VersionConstraint::GE, VersionConstraint::ANY => $compared >= 0,
            VersionConstraint::LE => $compared <= 0,
            VersionConstraint::LT => $compared < 0,
            VersionConstraint::GT => $compared > 0
        };
    }
}
