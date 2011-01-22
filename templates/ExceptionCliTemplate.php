<?php echo get_class($e); ?>
<?php echo $e->getMessage(); ?>

Type: <?php echo $type; ?>
File: <?php echo $e->getFile(); ?>
Line: <?php echo $e->getLine(); ?>

Trace:
<?php echo $e->getTraceAsString(); ?>
