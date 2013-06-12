<?php
namespace Corneltek\PreviewSystem;
use Corneltek\PreviewSystem\TemplateDirs;
use Corneltek\PreviewSystem\VirtualPath;
use Corneltek\PreviewSystem\MySplFileInfo;



/**
 * Synopsis.
 *
 * $preview = new Preview(array( 
 *   'template_dir' => array( .... )
 *   'enable_qrcode' => false,
 * ));
 * $preview->addTemplateDir($dir);
 *
 * $preview->updateSass('preview');
 * $preview->renderFile( $sourcePath, $targetPath );
 * $preview->renderDirectory( $source , $dest );
 *
 *
 * To preview one single file:
 *
 *     $preview->preview( $sourceFile );
 *
 * @VERSION 1.0.2
 */

class Preview
{

    public function __construct()
    {

    }


    static public function get_hg_bin()
    {
        return \futil_findbin('hg');
    }

    static public function get_hg_revision()
    {
        $hg = self::get_hg_bin();
        $rev = null;
        $hash = null;
        if($hg) {
            $revinfoStr = explode(":",trim(shell_exec("$hg tip | grep changeset | sed -e s/changeset://")));
            if(!empty($revinfoStr)) {
                return $revinfoStr;
            }
        }
        return false;
    }

    static public function get_twig($loader = null)
    {
        if( ! $loader ) {
            $template_dirs = TemplateDirs::get();
            $loader = new Twig_Loader_Filesystem( $template_dirs );
        }

        # $twig = new Twig_Environment($loader, array( 'cache' => 'cache',));
        $twig = new \Twig_Environment($loader, array(
            'auto_reload' => true,
        ) );

        // use spl class loader to check class.
        if ( class_exists('Twig_Extensions_Extension_Text',true) ) {
            $text = new \Twig_Extensions_Extension_Text;
            $twig->addExtension( $text );
        }

        if ( class_exists('Twig_Extensions_Extension_Debug',true) ) {
            $debug = new \Twig_Extensions_Extension_Debug;
            $twig->addExtension( $debug );
        }

        if( class_exists('Twig_Extension_Markdown',true) ) {
            $markdown = new \Twig_Extension_Markdown;
            $twig->addExtension( $markdown );
        }
        return $twig;
    }


    static public function render_file($file)
    {



    }

}


