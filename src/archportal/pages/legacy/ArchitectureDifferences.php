<?php

namespace archportal\pages\legacy;

use archportal\lib\Page;
use Symfony\Component\HttpFoundation\Request;

class ArchitectureDifferences extends Page
{
    public function prepare(Request $request)
    {
        $this->redirectPermanentlyToUrl('https://www.archlinux.org/packages/differences/');
    }
}
