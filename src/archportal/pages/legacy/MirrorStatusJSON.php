<?php

namespace archportal\pages\legacy;

use archportal\lib\Page;
use Symfony\Component\HttpFoundation\Request;

class MirrorStatusJSON extends Page
{
    public function prepare(Request $request)
    {
        $this->redirectPermanentlyToUrl('https://www.archlinux.org/mirrors/status/json/');
    }
}
