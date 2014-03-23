<?php

namespace archportal\templates;

use archportal\lib\Config;

?><!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title><?= Config::get('common', 'sitename'); ?> - <?= get_class($e); ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" media="screen" href="style/arch.css?v=4" />
	<link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2" />
	<link rel="shortcut icon" href="style/favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-exception">
		<div id="archnavbarlogo"><h1><a href="/">Arch Linux</a></h1></div>
		<div id="archnavbarmenu">
		<ul id="archnavbarlist">
			<?= $l10n->getTextFile('PageMenu'); ?>
		</ul>
		</div>
	</div>
	<div id="content">
		<div id="error">
		<h2><?= get_class($e); ?></h2>
		<p><?= sprintf($l10n->getText('I am sorry, something went wrong while processing file %s'), '<strong>'.basename($e->getFile(), '.php').'</strong>.'); ?></p>
		<p><?= sprintf($l10n->getText('Contact %s'), '<a href="mailto:'.Config::get('common', 'email').'">'.Config::get('common', 'email').'</a>'); ?></p>
		</div>
		<div id="footer">
			<?= $l10n->getTextFile('PageFooter'); ?>
		</div>
	</div>
</body>
</html>
