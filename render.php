#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';
use Corneltek\Preview\TaskRunner;
use Corneltek\Preview\ConfigLoader;
use ConfigKit\ConfigCompiler;

define('ENABLE_JAVA_I18N_FILTER', true);

if (file_exists('config/preview.yml')) {
    $configArray = ConfigCompiler::load('config/preview.yml');
    $config = new ConfigLoader($configArray);
} else {
    $config = new ConfigLoader();
    $config->loadFileIfExists('config/preview.yml');
}

$runner = new TaskRunner($config->stash);
$runner->run();
echo "\nDone\n";
