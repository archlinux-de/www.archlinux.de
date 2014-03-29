<?php

namespace archportal\templates;

use archportal\lib\Config;
?><!DOCTYPE HTML>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <title><?= Config::get('common', 'sitename'); ?> - <?= $this->getTitle(); ?></title>
        <meta name="robots" content="<?= $this->getMetaRobots(); ?>" />
        <?php
        foreach ($this->getCSS() as $cssFile) {
            ?>
            <link rel="stylesheet" media="screen" href="style/<?= $cssFile ?>.css" />
            <?php
        }
        foreach ($this->getJS() as $jsFile) {
            ?>
            <script type="text/javascript" src="style/<?= $jsFile ?>.js"></script>
            <?php
        }
        ?>
        <link rel="alternate" type="application/atom+xml" title="<?= $this->l10n->getText('Recent news'); ?>" href="<?= Config::get('news', 'feed'); ?>" />
        <link rel="alternate" type="application/atom+xml" title="<?= $this->l10n->getText('Recent Arch Linux packages'); ?>" href="<?= $this->createUrl('GetRecentPackages'); ?>" />
        <link rel="search" type="application/opensearchdescription+xml" title="<?= $this->l10n->getText('Search for Arch Linux packages'); ?>" href="<?= $this->createUrl('GetOpenSearch'); ?>" />
        <link rel="shortcut icon" href="style/favicon.ico" />
    </head>
    <body>
        <div id="archnavbar" class="anb-<?= strtolower($this->getName()); ?>">
            <div id="archnavbarlogo"><h1><a href="<?= $this->createUrl('Start'); ?>">Arch Linux</a></h1></div>
            <div id="archnavbarmenu">
                <ul id="archnavbarlist">
                    <?= $this->l10n->getTextFile('PageMenu'); ?>
                </ul>
            </div>
        </div>
        <div id="content">
            <?= $this->getBody(); ?>
            <div id="footer">
                <?= $this->l10n->getTextFile('PageFooter'); ?>
            </div>
        </div>
    </body>
</html>
