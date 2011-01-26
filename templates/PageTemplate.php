<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo Input::getHost(); ?> - <?php echo $this->getValue('title'); ?></title>
	<meta name="robots" content="<?php echo $this->getValue('meta.robots'); ?>" />
	<link rel="stylesheet" media="screen" href="style/arch.css?v=4" />
	<link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2" />
	<link rel="alternate" type="application/atom+xml" title="<?php
		echo $this->l10n->getText('Recent news');
	?>" href="<?php
		echo Config::get('news', 'feed');
	?>" />
	<link rel="alternate" type="application/atom+xml" title="<?php
		echo $this->l10n->getText('Recent Arch Linux packages');
	?>" href="<?php
		echo $this->createUrl('GetRecentPackages');
	?>" />
	<link rel="search" type="application/opensearchdescription+xml" title="<?php
		echo $this->l10n->getText('Search for Arch Linux packages');
	?>" href="<?php
		echo $this->createUrl('GetOpenSearch');
	?>" />
	<script type="text/javascript" src="style/jquery.min.js?v=1.4.4"></script>
	<script type="text/javascript" src="style/jquery-ui-autocomplete.min.js?v=1.8.8"></script>
	<link rel="shortcut icon" href="style/favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-<?php echo strtolower($this->getName()); ?>">
		<div id="archnavbarlogo"><h1><a href="?page=Start">Arch Linux</a></h1></div>
		<div id="archnavbarmenu">
		<ul id="archnavbarlist">
			<?php echo $this->l10n->getTextFile('PageMenu'); ?>
		</ul>
		</div>
	</div>
	<div id="content">
		<?php echo $this->getValue('body'); ?>
		<div id="footer">
			<?php echo $this->l10n->getTextFile('PageFooter'); ?>
		</div>
	</div>
</body>
</html>
