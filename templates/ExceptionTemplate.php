<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo Input::getHost(); ?> - <?php echo get_class($e); ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" media="screen" href="style/arch.css?v=4" />
	<link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2" />
	<link rel="shortcut icon" href="style/favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-exception">
		<div id="archnavbarlogo"><h1><a href="?page=Start">Arch Linux</a></h1></div>
		<div id="archnavbarmenu">
		<ul id="archnavbarlist">
			<?php echo $this->l10n->getTextFile('PageMenu'); ?>
		</ul>
		</div>
	</div>
	<div id="content">
		<div id="error">
		<h2><?php echo get_class($e); ?></h2>
		<p><?php echo sprintf($this->l10n->getText('I am sorry, something went wrong while processing file %s'), '<strong>'.basename($e->getFile(), '.php').'</strong>.'); ?></p>
		<p><?php echo sprintf($this->l10n->getText('Contact %s'), '<a href="mailto:'.Config::get('common', 'email').'">'.Config::get('common', 'email').'</a>'); ?></p>
		</div>
		<div id="footer">
			<?php echo $this->l10n->getTextFile('PageFooter'); ?>
		</div>
	</div>
</body>
</html>
