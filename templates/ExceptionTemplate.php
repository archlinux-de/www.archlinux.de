<!DOCTYPE HTML>
<html>
<head>
	<meta name="robots" content="noindex,nofollow" />
	<title><?php echo get_class($e); ?></title>
	<link rel="stylesheet" media="screen" href="style/arch.css?v=4" />
	<link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2" />
	<link rel="shortcut icon" href="style/favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-exception">
		<div id="archnavbarlogo"><h1><a href="?page=Start">Arch Linux</a></h1></div>
		<div id="archnavbarmenu">
		<ul id="archnavbarlist">
			<li id="anb-start"><a href="?page=Start">Start</a></li>
			<li id="anb-packages"><a href="?page=Packages">Pakete</a></li>
			<li id="anb-forum"><a href="https://bbs.archlinux.de/">Forum</a></li>
			<li id="anb-wiki"><a href="https://wiki.archlinux.de/">Wiki</a></li>
			<li id="anb-download"><a href="https://wiki.archlinux.de/title/Download">Download</a></li>
			<li id="anb-spenden"><a href="https://wiki.archlinux.de/title/Spenden">Spenden</a></li>
		</ul>
		</div>
	</div>
	<div id="content">
		<div id="error">
		<h2><?php echo get_class($e); ?></h2>
		<p>I am sorry, something went wrong while processing file <strong><?php echo basename($e->getFile(), '.php'); ?></strong>.</p>
		<p>Contact <a href="mailto:<?php echo Config::get('common', 'email'); ?>"><?php echo Config::get('common', 'email'); ?></a></p>
		</div>
	</div>
</body>
</html>
