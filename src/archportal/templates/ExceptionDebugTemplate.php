<?php

namespace archportal\templates;

use archportal\lib\Config;

?><!DOCTYPE HTML>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title><?= Config::get('common', 'sitename'); ?> - <?= get_class($e); ?></title>
    <meta name="robots" content="noindex,nofollow"/>
    <link rel="stylesheet" media="screen" href="style/arch.css?v=4"/>
    <link rel="stylesheet" media="screen" href="style/archnavbar.css?v=2"/>
    <link rel="shortcut icon" href="style/favicon.ico"/>
</head>
<body>
<div id="archnavbar" class="anb-exception">
    <div id="archnavbarlogo"><h1><a href="/">Arch Linux</a></h1></div>
    <div id="archnavbarmenu">
        <ul id="archnavbarlist"></ul>
    </div>
</div>
<div id="content">
    <div id="error">
        <h2><?= get_class($e); ?></h2>
        <pre><?= $e->getMessage(); ?></pre>
        <br/>
        <pre><strong>Type</strong>: <?= $type; ?></pre>
        <pre><strong>File</strong>: <?= htmlspecialchars($e->getFile()); ?></pre>
        <pre><strong>Line</strong>: <?= $e->getLine(); ?></pre>
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
        <pre><?= htmlspecialchars($e->getTraceAsString()); ?></pre>
        <h3>Files:</h3>
        <pre><?= htmlspecialchars(implode("\n", $files)); ?></pre>
        <h3>Request:</h3>
                <pre><?php
                    foreach ($_REQUEST as $key => $value) {
                        echo '<strong>['.htmlspecialchars($key).']</strong> => '
                            .htmlspecialchars(print_r($value, true))
                            .'<br />';
                    }
                    ?></pre>
    </div>
    <div id="footer"></div>
</div>
</body>
</html>
