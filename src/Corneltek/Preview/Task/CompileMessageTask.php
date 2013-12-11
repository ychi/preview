<?php
namespace Corneltek\Preview\Task;
use Twig_Extensions_Extension_I18n;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Corneltek\Preview\GettextParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class CompileMessageTask extends BaseTask 
{


    public function renderToCache($scanDirs, $tmpDir = 'cache')
    {
        if ( ! file_exists($tmpDir) ) {
            mkdir($tmpDir, 0775, true);
        }
        foreach( $scanDirs as $tplDir ) {
            // iterate over all your templates
            $rdi = new RecursiveDirectoryIterator($tplDir);
            $rii = new RecursiveIteratorIterator($rdi , RecursiveIteratorIterator::LEAVES_ONLY) ;
            foreach ($rii as $file)
            {
                $loader = new Twig_Loader_Filesystem(array( $file->getPath() , $tplDir ));

                // force auto-reload to always have the latest version of the template
                $twig = new Twig_Environment($loader, array(
                    'cache' => $tmpDir,
                    'auto_reload' => true
                ));
                $twig->addExtension(new Twig_Extensions_Extension_I18n());

                // force compilation
                if ($file->isFile() && ( in_array($file->getExtension(), array('html','htm','twig')))  ) {
                    /*
                    if ( CLI ) {
                        echo "Compiling ", $rii->getSubPathname(), "\n";
                    }
                    */
                    $twig->loadTemplate($rii->getSubpathname());
                }
            }
        }

    }

    public function run() 
    {
        $this->info("Running command to parse i18n messages...");
        $scanPaths = (array) $this->config('paths');
        $csvOutput = $this->config('csv') ?: 'messages.csv';

        if ( file_exists('cache/twig') ) {
            $cacheDir = 'cache/twig';
        } else {
            $cacheDir = 'cache';
        }

        $this->info("Rendering templates to cache...");
        $this->renderToCache($scanPaths, $cacheDir);
        $parser = new GettextParser;

        $this->info("Parsing messages from $cacheDir...");
        $parser->parseDirectory($cacheDir);

        $this->info("Writing messages to csv file $csvOutput...");
        $parser->writeCsv($csvOutput);
    }
}
