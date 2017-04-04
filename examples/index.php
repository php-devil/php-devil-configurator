<?php
require dirname(__DIR__) . '/vendor/autoload.php';

echo '<h1>Configurator component example</h1>';

$loader = \PhpDevil\Common\Configurator\Loader::getInstance();
$loader->enableFileCaching(__DIR__ . '/cache');