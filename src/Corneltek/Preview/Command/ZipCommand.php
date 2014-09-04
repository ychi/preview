<?php
namespace Corneltek\Preview\Command;
use CLIFramework\Command;
use Corneltek\Preview\TaskRunner;
use Corneltek\Preview\ConfigLoader;
use Corneltek\Preview\Command\RenderCommand;

use ZipArchive;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ZipCommand extends Command
{
    public function brief() {
        return 'Render templates and create zip archive file';
    }

    public function options($opts)
    {
        $opts->add('c|config:', 'The config file which should be used.');
    }

    public function execute() {

        if (!extension_loaded('zip')) {
            return $this->logger->error('php zip extension is not installed or enabled.');
        }
        if ( !class_exists('ZipArchive')) {
            return $this->logger->error('ZipArchive class does not exist.');
        }

        $render = $this->createCommand('\Corneltek\Preview\Command\RenderCommand');
        $render->setOptions($this->getOptions());
        $render->execute();

        $zipFilename = sprintf("preview_%s.zip", date('Y-m-d') );

        if (file_exists($zipFilename)) {
            unlink($zipFilename);
        }


        $this->logger->info('Creating zip file...');
        $zip = new ZipArchive();

        if ($zip->open($zipFilename, ZipArchive::CREATE)!==TRUE) {
            exit("cannot open <$zipFilename>\n");
        }

        $path = 'preview';
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS), RecursiveIteratorIterator::SELF_FIRST);
        foreach($files as $name => $file){
            $this->logger->info("Adding to zip file: " . $name);
            if ($file->isFile()) {
                $zip->addFile($name);
            } elseif($file->isDir()) {
                $zip->addEmptyDir($name);
            }
        }

        $this->logger->info("Number of files: " . $zip->numFiles);
        $this->logger->info("Status: " . $zip->status);
        $zip->close();
    }
}
