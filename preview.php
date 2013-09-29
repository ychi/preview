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
$config->loadFileIfExists('config/preview.yml');

$preview = new Corneltek\Preview\Preview($config->stash);
$preview->dispatch(getPathInfo());
