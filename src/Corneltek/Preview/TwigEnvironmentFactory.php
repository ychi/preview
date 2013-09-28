<?php
namespace Corneltek\Preview;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_Extension_Markdown;
use Twig_SimpleFunction;
use Twig_Extensions_Extension_Text;
use Twig_Extensions_Extension_I18n;
use Twig_Extensions_Extension_Debug;

class TwigEnvironmentFactory {

    static public function createWithLoader($loader, $options = array())
    {
        $twig = new Twig_Environment($loader , $options);
        $twig->addFunction(new Twig_SimpleFunction('time', 'time'));

        if ( class_exists('Twig_Extensions_Extension_I18n',true) ) {
            $twig->addExtension(new \Twig_Extensions_Extension_I18n());
        }

        if ( class_exists('Twig_Extensions_Extension_Text',true) ) {
            $twig->addExtension(new \Twig_Extensions_Extension_Text);
        }

        if ( class_exists('Twig_Extensions_Extension_Debug',true) ) {
            $debug = new \Twig_Extensions_Extension_Debug;
            $twig->addExtension( $debug );
        }

        if( class_exists('Twig_Extension_Markdown',true) ) {
            $twig->addExtension( new \Twig_Extension_Markdown );
        }
        return $twig;
    }


    static public function create($dirs = array(), $options = array())
    {
        $loader = new Twig_Loader_Filesystem($dirs);

        // register 'preview' template namespace:  src/Corneltek/Preview/Templates
        $builtInTemplateDir = __DIR__ . DIRECTORY_SEPARATOR . 'Templates';
        if ( file_exists($builtInTemplateDir) ) {
            $loader->addPath($builtInTemplateDir, 'preview');
        }
        return self::createWithLoader($loader, $options);
    }
}
