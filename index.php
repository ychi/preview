<?php
require 'vendor/autoload.php';
use Corneltek\Preview\Preview;
use Corneltek\Preview\ConfigLoader;
use ConfigKit\ConfigCompiler;

function getPathInfo()
{
    if ( isset($_SERVER['PATH_INFO']) ) {
        return ltrim($_SERVER['PATH_INFO'],'/');
    }
}


if (file_exists('config/preview.yml')) {
    $configArray = ConfigCompiler::load('config/preview.yml');
    $config = new ConfigLoader($configArray);
} else {
    $config = new ConfigLoader();
    $config->loadFileIfExists('config/preview.yml');
}

$path = getPathInfo();
if ( ! $path ) {
    // redirect to design by default.
    header('Location: index.php/design/');
    exit(0);
}

$preview = new Preview($config->stash);
$preview->dispatch($path);
