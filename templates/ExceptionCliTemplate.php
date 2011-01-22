<?php echo get_class($e); ?>
<?php echo $e->getMessage(); ?>

Type: <?php echo $type; ?>
File: <?php echo $e->getFile(); ?>
Line: <?php echo $e->getLine(); ?>

Context:
<?php
foreach ($context as $line => $content) {
	echo ++$line.' '.$context."\n";
}
?>

Trace:
<?php echo $e->getTraceAsString(); ?>

Files:
<?php echo implode("\n", $files); ?>
