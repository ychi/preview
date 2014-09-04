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
        $zipBin = null;
        if (!extension_loaded('zip') ||  !class_exists('ZipArchive')) {
            $this->logger->warn('php zip extension is not installed or enabled. fallback to zip command');
            if ($zipBin = futil_findbin('zip')) {
                $this->logger->info("$zipBin found.");
            } else {
                return $this->logger->error("$zipBin not found.");
            }
        }

        $render = $this->createCommand('\Corneltek\Preview\Command\RenderCommand');
        $render->setOptions($this->getOptions());
        $render->execute();

        $previewDir = 'preview';
        $zipFilename = sprintf("preview_%s.zip", date('Y-m-d') );
        if (file_exists($zipFilename)) {
            unlink($zipFilename);
        }


        $this->logger->info('Creating zip file...');
        if ($zipBin) {
            system("$zipBin -r $zipFilename $previewDir");
        } else {
            $zip = new ZipArchive();
            if ($zip->open($zipFilename, ZipArchive::CREATE)!==TRUE) {
                exit("cannot open <$zipFilename>\n");
            }

            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($previewDir, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS), RecursiveIteratorIterator::SELF_FIRST);
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
}
