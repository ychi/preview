<?php
namespace Corneltek\Preview;
use Symfony\Component\Yaml\Yaml;
use ArrayAccess;

class ConfigLoader implements ArrayAccess
{

    public $stash = array();

    public function __construct() {
        $this->stash = array(
            'TemplateDir'      => 'design',
            'PreviewDir'       => 'preview',
            'StaticDir'        => 's',
            'Verbose'          => false,
            'Twig'             => array(
                // 'cache' => true,
            ),
        );
    }

    public function loadFileIfExists($file) {
        if ( file_exists($file) ) {
            $this->stash = array_merge($this->stash,Yaml::parse($file));
        }
    }

    public function loadFile($file) {
        $this->stash = array_merge($this->stash,Yaml::parse($file));
    }
    
    public function offsetSet($name,$value)
    {
        $this->stash[ $name ] = $value;
    }
    
    public function offsetExists($name)
    {
        return isset($this->stash[ $name ]);
    }
    
    public function offsetGet($name)
    {
        return $this->stash[ $name ];
    }
    
    public function offsetUnset($name)
    {
        unset($this->stash[$name]);
    }
}
