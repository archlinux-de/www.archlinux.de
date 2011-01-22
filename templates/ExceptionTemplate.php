<!DOCTYPE HTML>
<html>
<head>
<title>Schwerer Fehler</title>
</head>
<body>
	<h1 style="font-size:16px;">Fehler in Modul <?php echo get_class($e); ?></h1>
	<p>Es ist ein schwerer Fehler aufgetreten. Die Administration wurde bereits benachrichtigt. Das Problem wird sobald wie möglich behoben.</p>
	<h2 style="font-size:14px;">Kontakt</h2>
	<p><a href="mailto:<?php echo Modul::get('Settings')->getValue('email'); ?>"><?php echo Modul::get('Settings')->getValue('email'); ?></a></p>
</body>
</html>
