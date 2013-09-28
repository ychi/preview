#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';
use Corneltek\Preview\TaskRunner;
use Corneltek\Preview\ConfigLoader;

$config = new ConfigLoader;
if ( file_exists('config.yml') ) {
    $config->loadFile('config.yml');
}

$runner = new TaskRunner($config->stash);
$runner->run();
echo "\nDone\n";
