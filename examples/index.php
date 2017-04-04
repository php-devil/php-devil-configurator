<?php
require dirname(__DIR__) . '/vendor/autoload.php';

echo '<h1>Configurator component example</h1>';

$loader = \PhpDevil\Common\Configurator\Loader::getInstance();
$loader->enableFileCaching(__DIR__ . '/cache');

$fromPHP = $loader->load(__DIR__ . '/configs/sample.config.php');
$fromPHPCached = $loader->load(__DIR__ . '/configs/sample.config.php');

$fromYML = $loader->load(__DIR__ . '/configs/sample.config.yml');
$fromYMLCached = $loader->load(__DIR__ . '/configs/sample.config.yml');

$fromJSON = $loader->load(dirname(__DIR__) . '/composer.json');

echo "<pre>";
print_r($fromPHP);
print_r($fromPHPCached);

print_r($fromYML);
print_r($fromYMLCached);

print_r($fromJSON);