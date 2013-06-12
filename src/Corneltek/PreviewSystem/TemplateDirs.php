<?php
namespace Corneltek\PreviewSystem;

class TemplateDirs 
{
    static $dirs = array();

    static function add($dir) 
    {
        self::$dirs[] = $dir;
    }

    static function get() 
    {
        return self::$dirs;
    }
}


