<?php

namespace App\Service;

class NewsItemFilter extends \HTMLPurifier_Filter
{
    /** @var string */
    public $name = __CLASS__;

    /**
     * @param string $html
     * @param \HTMLPurifier_Config $config
     * @param \HTMLPurifier_Context $context
     * @return string
     */
    public function preFilter($html, $config, $context)
    {
        // FluxBB wrapps every content within <li> into <p>; let's remove these
        return (string)preg_replace('#<li><p>(.+?)</p></li>#', '<li>$1</li>', $html);
    }
}
