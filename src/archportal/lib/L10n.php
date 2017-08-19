<?php

namespace archportal\lib;

class L10n
{
    /** @var array */
    private $localeInfo = array();
    /** @var string */
    private $locale = '';

    public function __construct()
    {
        $this->locale = Config::get('l10n', 'locale');

        putenv('LC_ALL=' . $this->locale);
        setlocale(LC_ALL, $this->locale);
        date_default_timezone_set(Config::get('l10n', 'timezone'));
        $this->localeInfo = localeconv();
        bindtextdomain('archportal', __DIR__ . '/../l10n');
        textdomain('archportal');
    }

    /**
     * @param string $text
     *
     * @return string
     */
    public function getText(string $text): string
    {
        return gettext($text);
    }

    /**
     * @param int|null $timestamp
     *
     * @return string
     */
    public function getDateTime($timestamp = null): string
    {
        return date($this->getText('Y-m-d H:i'), $timestamp);
    }

    /**
     * @param int|null $timestamp
     *
     * @return string
     */
    public function getGmDateTime($timestamp = null): string
    {
        return gmdate('Y-m-d H:i', $timestamp);
    }
}
