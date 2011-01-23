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
		<pre><?php echo $e->getMessage(); ?></pre><br />
		<pre><strong>Type</strong>: <?php echo $type; ?></pre>
		<pre><strong>File</strong>: <?php echo htmlspecialchars($e->getFile()); ?></pre>
		<pre><strong>Line</strong>: <?php echo $e->getLine(); ?></pre>
		<h3>Context:</h3>
		<pre id="error-context"><?php
		foreach ($context as $line => $content) {
			echo ++$line.' ';
			if ($line === $e->getLine()) {
				echo '<strong>'.htmlspecialchars($content).'</strong>';
			} else {
				echo htmlspecialchars($content);
			}
			echo "\n";
		}
		?></pre>
		<h3>Trace:</h3>
		<pre><?php echo htmlspecialchars($e->getTraceAsString()); ?></pre>
		<h3>Files:</h3>
		<pre><?php echo htmlspecialchars(implode("\n", $files)); ?></pre>
		</div>
	</div>
</body>
</html>
