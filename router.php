<?php
use Corneltek\Preview\Preview;
use Corneltek\Preview\ConfigLoader;

// Parse REQUEST_URI, extract the file path and see if it's a static file,
// If it is a static file, we just return false, to let PHP built-in server handles it.
$info = parse_url( $_SERVER["REQUEST_URI"] );
if (preg_match('/\.(?:png|jpg|jpeg|gif|css|js|woff|ttf|svg)$/', $info['path'])) {
    return false;    // serve the requested resource as-is.
}

// If it's not a static file, we pass the path to our preview system to render it.
require 'vendor/autoload.php';
$path = ltrim($info['path'],'/');
$config = new ConfigLoader;
$config->loadFileIfExists('config/preview.yml');
$preview = new Preview($config->stash);
$preview->dispatch($path);
return true;
