<?php
namespace Corneltek\Preview;
use SplFileInfo;

/**
 * @VERSION 2.0.1
 **/
class Preview {

    public $config = array();

    public function __construct($config = array()) {
        $this->config = $config;
    }

    public function getTwigEnvironmentByPath($pathinfo)
    {
        $dirs = array(
            $pathinfo->getPath(),
            'design',
            getcwd(),
        );
        return TwigEnvironmentFactory::create($dirs);
    }

    public function redirectToStaticFile($path)
    {
        $fn = $_SERVER['SCRIPT_NAME'];
        // var_dump( dirname( $fn ) );
        $url = dirname($fn) . '/' . $path;
        // header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        HttpHeaderMessage::byCode(301);
        header("Location: $url");
    }

    public function renderTemplate($fileinfo)
    {
        $dirs = array(
            $fileinfo->getPath(),
            'design',
            getcwd(),
        );
        $twig = $this->getTwigEnvironmentByPath($fileinfo);
        $twig = \Corneltek\Preview\TwigEnvironmentFactory::create($dirs);

        $templateFile = $fileinfo->getFilename();
        $template = $twig->loadTemplate( $templateFile );
        $content = $template->render(array());

        // filter out java i18n tag
        $content = preg_replace('#{(?<TAG>\w+).*?}(.*?){/\k<TAG>}#', '$2', $content );
        echo $content;
    }

    public function dispatch($path) {
        $fileinfo = new SplFileInfo($path);

        if ( ! $fileinfo->isReadable() ) {
            HttpHeaderMessage::byCode(404);
            echo 'Page Not Found.';
            return;
        }
        else if( $fileinfo->isDir() ) {
            $index = new DirectoryIndexReader( $path );
            $index->display();
            return;
        }
        elseif( $fileinfo->isFile() ) {
            $ext = $fileinfo->getExtension();
            switch($ext) {
                case 'html':
                case 'htm':
                case 'twig':
                    $this->renderTemplate($fileinfo);
                    break;
                case 'php':
                    require $path;
                    break;
                default:
                    return $this->redirectToStaticFile($path);
            }
        }
    }
}


