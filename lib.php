<?php
namespace Corneltek\Preview;
use Corneltek\ScssRunner;
use Corneltek\SassRunner;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

abstract class BaseTask {
    public $desc;

    public $stash = array();

    public $config;

    public $logger;

    public function __construct($config = array()) {
        $this->config = $config;
        $this->logger = ConsoleLogger::getInstance();
    }

    public function __call($m, $a) {
        if ( method_exists($this->logger, $m) ) {
            return call_user_func_array(array($this->logger, $m ), $a);
        }
    }

    public function getDesc() {
        return $this->desc;
    }

    public function getName() {
        return get_class($this);
    }

    public function getConfig() {
        return $this->config;
    }

    public function __get($key)
    {
        if ( isset($this->stash[$key]) ) {
            return $this->stash[$key];
        }
    }

    public function __isset($key) 
    {
        return isset($this->stash[$key]);
    }

    public function __set($key, $value) {
        $this->stash[ $key ] = $value;
    }

    public function config($key) {
        if ( isset($this->config[$key]) ) {
            return $this->config[$key];
        }
    }

    abstract public function run();
}

class CleanTask extends BaseTask
{
    public function run() {
        if ($paths = $this->config('paths') ) {
            foreach( $paths as $path ){
                if ( file_exists($path) ) {
                    futil_rmtree($path);
                }
            }
        }
    }
}

class CleanPreviewTask extends BaseTask
{
    public function run()
    {
        $this->info("Cleaning preview directory...");
        if ( $dir = $this->config('PreviewDir') ) {
            if ( file_exists($dir) ) {
                futil_rmtree($dir);
            }
        }
    }
}

class CopyDesignTask extends BaseTask
{
    public function run()
    {
        $this->info("Copying design files to preview directory...");
        $templateDir = $this->config('TemplateDir');
        $previewDir = $this->config('PreviewDir');

        if ( ! $templateDir || ! $previewDir ) {
            return $this->fatal("TemplateDir and PreviewDir required.");
        }

        if ( $this->config('rsync') ) {
            system("rsync -r $templateDir/ $previewDir/");
        } else {
            rcopy($templateDir, $previewDir);
        }
    }
}

class CompileScssTask extends BaseTask {

    public function run() 
    {
        $this->info("Running scss to compile scss files...");
        $templateDir = $this->config('TemplateDir');
        $staticDir = $this->config('StaticDir');
        $scss = new ScssRunner;

        // default scss directory
        $scss->addTarget("$templateDir/$staticDir/scss", "$templateDir/$staticDir/css");

        // extra scss directory
        if ( $paths = $this->config('paths') ) {
            foreach( $paths as $path ) {
                if ( false !== strpos($path, ':') ) {
                    list($src,$dst) = explode(':',$path);
                    $scss->addTarget($src, $dst);
                } else {
                    $scss->addTarget($path);
                }
            }
        }
        if ( $this->config('compass') ) {
            $scss->enableCompass();
        }
        $scss->update($this->config('force'));
    }
}


class ChmodReadWriteTask extends BaseTask {

    public function run()
    {
        // for windows, we don't need chmod.
        if ( PHP_OS !== 'Windows' && PHP_OS !== 'WINNT' ) {
            $this->info("Chmoding to +read/write...");
            if ( $dir = $this->config('templateDir') ) {
                system("chmod -R a+rw " . $dir);
            }
        }
    }
}

class TemplateFile {

    public $twig;
    public $file;

    public function __construct($file, $twig) {
        $this->file = $file;
        $this->twig = $twig;
    }

    public function getDir()
    {
        return $this->file->getPath();
    }

    public function render($data = array() ) {
        $template = $this->twig->loadTemplate( $this->file->getFilename() );
        return $template->render($data);
    }

}

class CopyTask extends BaseTask
{
    public function run() {
        if ( $paths = $this->config('paths') ) {
            foreach( $paths as $path ) {
                if ( false !== strpos($path,':') ) {
                    list($src,$dest) = explode(':',$path);
                    $this->info("Copy $src -> $dest");
                    rcopy($src, $dest);
                } else {
                    $this->fatal('paths require src:dest format string');
                }
            }
        }
    }
}


class RenderPreviewTask extends BaseTask
{

    public function run() {
        $templateDir = $this->config('TemplateDir');
        $previewDir = $this->config('PreviewDir');

        // in the netbase repository we includes google closure, we can't
        // compile the closure template.
        if ( $subfolders = $this->config('subfolders') ) {
            foreach( $subfolders as $f ) {
                $this->renderDirectory("$templateDir/$f", "$previewDir/$f");
            }
        } else {
            // by default, we render all the files under the template directory.
            $this->renderDirectory($templateDir, $previewDir);
        }
    }

    public function renderFile($sourceFilePath, $sourceDir, $targetFilePath, $targetDir , $baseData = array() ) 
    {
        futil_mkdir_if_not_exists(dirname($targetFilePath), 0755, true);
        if ( ! preg_match('/\.(html?|twig)$/', $sourceFilePath ) ) {
            $this->info("Copy $sourceFilePath -> $targetFilePath");
            futil_copy_if_newer($sourceFilePath, $targetFilePath);
            return false;
        }

        if ( file_exists($targetFilePath) ) {
            // mtime older or equal
            if ( futil_mtime_compare($sourceFilePath, $targetFilePath) < 1 ) {
                $this->info("Skip $sourceFilePath, not modified.");
                return false;
            }
        }

        /* looking for twig or html files */
        $templateDirs =  array(
            $sourceFilePath->getPath(),
            $sourceDir,
            $this->config('TemplateDir'),
            getcwd(),
        );
        if ( $dirs = $this->config('templateDirs') ) {
            if ( is_array($dirs) ) {
                $templateDirs = array_merge($templateDirs, $dirs);
            }
        }

        $this->debug("Using template directories:" . print_r($templateDirs,true) );
        $templateFile = new TemplateFile($sourceFilePath,
            TwigEnvironmentFactory::create($templateDirs, $this->config('Twig')));

        /* put rendered content into preview dir */
        if ( $this->logger->isVerbose() ) {
            $this->info("Rendering $sourceFilePath -> $targetFilePath...");
        } else {
            echo ".";
        }

        $content = $templateFile->render($baseData);

        // filter out java i18n tag
        if (ENABLE_JAVA_I18N_FILTER) {
            $content = preg_replace('#{(?<TAG>\w+).*?}(.*?){/\k<TAG>}#', '$2', $content );
        }

        if ( false === file_put_contents( $targetFilePath, $content ) ) {
            die("Error: can not render file. $targetFilePath.");
        }
    }

    public function renderDirectory($sourceDir, $targetDir, $baseData = array())
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($iterator as $sourceFile) {
            if ( ! $sourceFile->isFile() ) {
                continue;
            }
            $sourceFileSubPath = $iterator->getSubPathName();
            $previewFilePath = $targetDir . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            # $sourceFilePath = $sourceFile->getPathname();
            $this->renderFile(
                $sourceFile, $sourceDir,
                $previewFilePath, $targetDir,
                $baseData);
        }
    }
}

defined('ENABLE_JAVA_I18N_FILTER') || define('ENABLE_JAVA_I18N_FILTER', false);

function rcopy($source, $dest)
{
    if ( ! file_exists($dest) ) {
        mkdir($dest, 0755, true);
    }

    foreach (
        $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST) as $item
    ) 
    {
        $targetPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        if ($item->isDir()) {
            futil_mkdir_if_not_exists($targetPath, 0755, true);
        } else {
            futil_copy_if_newer($item, $targetPath);
        }
    }
}

