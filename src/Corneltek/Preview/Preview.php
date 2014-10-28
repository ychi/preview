<?php
namespace Corneltek\Preview;
use SplFileInfo;
use Phifty\Locale;
use ConfigKit\ConfigCompiler;

/**
 * @VERSION 2.2.2
 **/
class Preview {

    public $config = array();

    public function __construct($config = array()) {
        global $locale;
        if (extension_loaded('gettext')) {
            $locale  = new Locale;
            $locale->domain('preview');
            $locale->localedir('locale');
            // default lang to zh_TW

            if ( isset($_REQUEST['locale']) ) {
                $locale->init($_REQUEST['locale']);
            } else {
                // setup to default locale
                $locale->init('zh_TW');
            }
        }
        $this->config = $config;
    }

    public function getTwigEnvironmentByPath($pathinfo, $options = array())
    {
        $dirs = array(
            $pathinfo->getPath(),
            'design',
            getcwd(),
        );
        return TwigEnvironmentFactory::create($dirs, $options);
    }

    public function redirectToStaticFile($path)
    {
        $fn = $_SERVER['SCRIPT_NAME'];
        $baseUrl = rtrim(dirname($fn),'/');
        $url = $baseUrl . '/' . $path;
        header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
        HttpHeaderMessage::byCode(301);
        header("Location: $url");
    }

    public function renderTemplate($fileinfo)
    {
        $twig = $this->getTwigEnvironmentByPath($fileinfo, array(
            'cache' => getcwd() . DIRECTORY_SEPARATOR . 'cache',
            'auto_reload' => true,
        ));
        $templateFile = $fileinfo->getFilename();

        $pathInfo = pathinfo($fileinfo->getRealPath());
        $configFile = $pathInfo['dirname'] . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.yml';
        $config = array();
        if (file_exists($configFile)) {
            $config = ConfigCompiler::load($configFile);
        }
        $template = $twig->loadTemplate( $templateFile );
        return $template->render(array(
            'Request'  => $_REQUEST,
            'Get'      => $_GET,
            'Post'     => $_POST,
            'Server'   => $_SERVER,
            'Config'   => $config,
        ));
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
                    $start = microtime(true);
                    $content = $this->renderTemplate($fileinfo);
                    $end = microtime(true);
                    $used = ($end - $start) * 1000;
                    header("X-Rendering-Time: {$used}ms");
                    echo $content;
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


