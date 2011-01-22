<!DOCTYPE HTML>
<html>
<head>
	<meta name="robots" content="<?php echo $this->getValue('meta.robots'); ?>" />
	<title>archlinux.de - <?php echo $this->getValue('title'); ?></title>
	<link rel="stylesheet" media="screen" href="style/arch.css?v=4" />
	<link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2" />
	<link rel="alternate" type="application/atom+xml" title="Aktuelle Ankündigungen" href="<?php echo $this->Settings->getValue('news_feed'); ?>" />
	<link rel="alternate" type="application/atom+xml" title="Aktualisierte Pakete" href="?page=GetRecentPackages" />
	<link rel="search" type="application/opensearchdescription+xml" href="?page=GetOpenSearch" title="www.archlinux.de" />
	<link rel="shortcut icon" href="style/favicon.ico" />
</head>
<body>
	<div id="archnavbar" class="anb-<?php echo strtolower($this->getName()); ?>">
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
		<?php echo $this->getValue('body'); ?>
		<div id="footer">
			<a href="https://wiki.archlinux.de/title/wiki.archlinux.de:Datenschutz">Datenschutz</a> &ndash;
			<a href="https://wiki.archlinux.de/title/wiki.archlinux.de:Impressum">Impressum</a>
		</div>
	</div>
</body>
</html>
