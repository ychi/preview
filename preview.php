<?php
require 'vendor/autoload.php';

function getPathInfo()
{
    if ( isset($_SERVER['PATH_INFO']) ) {
        return ltrim($_SERVER['PATH_INFO'],'/');
    }
    return 'design';
}

$config = new ConfigLoader;
if ( file_exists('config.yml') ) {
    $config->loadFile('config.yml');
}

$preview = new Corneltek\Preview\Preview($config->stash);
$preview->dispatch(getPathInfo());
