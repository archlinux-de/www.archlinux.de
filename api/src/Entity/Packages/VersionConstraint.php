<?php

namespace App\Entity\Packages;

enum VersionConstraint: string
{
    /** No version constraint */
    case ANY = '';

    /** Test version equality (package=x.y.z) */
    case EQ = '=';

    /** Test for at least a version (package>=x.y.z) */
    case GE = '>=';

    /** Test for at most a version (package<=x.y.z) */
    case LE = '<=';

    /** Test for greater than some version (package>x.y.z) */
    case GT = '>';

    /** Test for less than some version (package<x.y.z) */
    case LT = '<';
}
