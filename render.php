#!/usr/bin/env php
<?php
require 'lib.php';
use Symfony\Component\Yaml\Yaml;
use Corneltek\Preview\TaskRunner;
use Corneltek\Preview\ConfigLoader;

// namespace Corneltek\Preview;
require_once 'vendor/autoload.php';
Twig_Autoloader::register();


$config = new ConfigLoader;
if ( file_exists('config.yml') ) {
    $config->loadFile('config.yml');
}

$runner = new TaskRunner($config->stash);
$runner->run();
echo "\nDone\n";
