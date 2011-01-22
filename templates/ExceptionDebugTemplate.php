<!DOCTYPE HTML>
<html>
<head>
<title><?php echo get_class($e); ?></title>
</head>
<body>
<h1 style="font-size:16px;"><?php echo get_class($e); ?></h1>
<pre style="overflow:auto;"><?php echo htmlspecialchars($e->getMessage()); ?></pre>
<pre>
<strong>Type</strong>: <?php echo $type; ?> 
<strong>File</strong>: <?php echo htmlspecialchars($e->getFile()); ?> 
<strong>Line</strong>: <?php echo $e->getLine(); ?></pre>
<h2 style="font-size:14px;">Trace:</h2>
<pre><?php echo htmlspecialchars($e->getTraceAsString()); ?></pre>
</body>
</html>
