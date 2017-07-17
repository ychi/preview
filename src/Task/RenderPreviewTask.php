<?php
namespace Corneltek\Preview\Task;
use Exception;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Corneltek\Preview\TemplateFile;
use Corneltek\Preview\TwigEnvironmentFactory;

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

        try {
            $content = $templateFile->render($baseData);
        } catch( Exception $e ) {
            echo $e->getFile() . ':' . $e->getLine() . ' ' . $e->getMessage() . "\n";
            return;
        }

        // filter out java i18n tag
        if (ENABLE_JAVA_I18N_FILTER) {
            $content = preg_replace('#{(?<TAG>\w+).*?}(.*?){/\k<TAG>}#', '$2', $content );
        }

        if ( false === file_put_contents( $targetFilePath, $content ) ) {
            echo "Error: can not render file. $targetFilePath.\n";
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
