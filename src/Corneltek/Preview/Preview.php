<?php
namespace Corneltek\Preview;
use SplFileInfo;
use Phifty\Locale;
use ConfigKit\ConfigCompiler;
use ReflectionObject;
use ReflectionClass;
use Pelago\Emogrifier;

/**
 * @VERSION 2.3.0
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

    public function getBuiltInTwigEnvironment(array $options = array())
    {
        $dirs = array();
        $class = new ReflectionClass($this);
        $classFilename = $class->getFilename();
        $templateDir = dirname($classFilename) . DIRECTORY_SEPARATOR . 'Templates';
        return TwigEnvironmentFactory::create(array(
            'preview' => $templateDir,
        ), $options);
    }

    public function getTwigEnvironmentByPath($pathinfo, $options = array())
    {
        $dirs = array(
            $pathinfo->getPath(),
            'design/pages',
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


    public function renderTemplate(SplFileInfo $fileinfo)
    {
        $twig = $this->getTwigEnvironmentByPath($fileinfo, array(
            'cache' => getcwd() . DIRECTORY_SEPARATOR . 'cache',
            'auto_reload' => true,
        ));

        $templateFile = $fileinfo->getFilename();
        $pathInfo = pathinfo($fileinfo->getRealPath());
        $templateDirectory = $pathInfo['dirname'];

        $configFile = $templateDirectory . DIRECTORY_SEPARATOR . $pathInfo['filename'] . '.yml';
        $globalConfigFile = $templateDirectory . DIRECTORY_SEPARATOR . '_global.yml';

        $previewTemplates = array();

        $defaultConfig = array();
        $defaultArguments = array(
            'Request'  => $_REQUEST,
            'Get'      => $_GET,
            'Post'     => $_POST,
            'Server'   => $_SERVER,
            'GlobalConfig' => array(),
            'Config'   => $defaultConfig,
        );

        if (file_exists($globalConfigFile)) {
            $defaultArguments['GlobalConfig'] = ConfigCompiler::load($globalConfigFile);
            $defaultArguments['Config'] = array_merge_recursive($defaultArguments['Config'], $defaultArguments['GlobalConfig']);
        }
        if (file_exists($configFile)) {
            $defaultArguments['Config'] = array_merge_recursive($defaultArguments['Config'], ConfigCompiler::load($configFile));
        }

        // TODO: Refactor this to plugin
        if (isset($defaultArguments['Config']['PageOptions'])) {
            $builtInTwig = $this->getBuiltInTwigEnvironment();
            $pageOptionsTemplate = $builtInTwig->loadTemplate('page_options.html.twig');
            $previewTemplates['PageOptions'] = $pageOptionsTemplate->render($defaultArguments);
        }
        $defaultArguments['PreviewTemplates'] = $previewTemplates;

        $template = $twig->loadTemplate( $templateFile );
        return $template->render($defaultArguments);
    }

    public function dispatch($path) {
        $fileinfo = new SplFileInfo($path);

        if (! $fileinfo->isReadable()) {

            HttpHeaderMessage::byCode(404);
            echo 'Page Not Found.';
            return;

        } else if ($fileinfo->isDir() ) {

            $index = new DirectoryIndexReader( $path );
            $index->display();
            return;

        } elseif ($fileinfo->isFile() ) {
            $ext = $fileinfo->getExtension();
            switch($ext) {
                case 'html':
                case 'htm':
                case 'twig':
                    $start = microtime(true);
                    $html = $this->renderTemplate($fileinfo);
                    $end = microtime(true);
                    $used = ($end - $start) * 1000;
                    header("X-Rendering-Time: {$used}ms");

                    if (isset($_REQUEST['_filters'])) {
                        if (strpos($_REQUEST['_filters'],'inline-style') !== false) {

                            if (!isset($_REQUEST['cssfile'])) {
                                throw new Exception("Please specify 'cssfile' parameter in the url.");
                            }

                            $cssFile = $_REQUEST['cssfile'];
                            $ext = pathinfo($cssFile, PATHINFO_EXTENSION);
                            if ($ext != "css") {
                                throw new InvalidArgumentException("Invalid css filename.");
                            }
                            if (!file_exists($cssFile)) {
                                throw new Exception("Css file does not exist.");
                            }
                            $css = file_get_contents($cssFile);
                            $emogrifier = new Emogrifier($html, $css);
                            $html = $emogrifier->emogrify();
                        }
                    }

                    echo $html;
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


