<?php
namespace Corneltek\Preview;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_Extension_Markdown;
use Twig_SimpleFunction;
use Twig_Extensions_Extension_Text;
use Twig_Extensions_Extension_I18n;
use Twig_Extensions_Extension_Debug;

class Public_Twig_Environment extends Twig_Environment {
    /**
     * This exists so template cache files use the same
     * group between apache and cli
     */
    protected function writeCacheFile($file, $content){
        if (!is_dir(dirname($file))) {
            $old = umask(0000);
            mkdir(dirname($file),0777,true);
            umask($old);
        }
        parent::writeCacheFile($file, $content);
        chmod($file,0666);
    }
}



class TwigEnvironmentFactory {

    static public function createWithLoader($loader, $options = array())
    {
        $twig = new Public_Twig_Environment($loader , $options);
        $twig->addFunction(new Twig_SimpleFunction('time', 'time'));
        $twig->addFunction(new Twig_SimpleFunction('override_query', function(array $args) {
            return http_build_query(array_merge($_GET,$args));
        }));

        if ( class_exists('Twig_Extensions_Extension_I18n') ) {
            $twig->addExtension(new \Twig_Extensions_Extension_I18n());
        }

        if ( class_exists('Twig_Extensions_Extension_Text') ) {
            $twig->addExtension(new \Twig_Extensions_Extension_Text);
        }

        if (class_exists('Twig_Extension_Debug')) {
            $debug = new \Twig_Extension_Debug;
            $twig->addExtension( $debug );
        } elseif (class_exists('Twig_Extensions_Extension_Debug')) {
            $debug = new \Twig_Extensions_Extension_Debug;
            $twig->addExtension( $debug );
        }
        

        if( class_exists('Twig_Extension_Markdown') ) {
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
