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
		<div id="footer">
			<?php echo $this->l10n->getTextFile('PageFooter'); ?>
		</div>
	</div>
</body>
</html>
