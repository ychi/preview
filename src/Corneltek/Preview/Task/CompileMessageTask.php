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


    public function renderToCache($scanDirs, $cacheDir = 'cache')
    {
        foreach( $scanDirs as $dir ) {
            // iterate over all your templates
            $rdi = new RecursiveDirectoryIterator($dir);
            $rii = new RecursiveIteratorIterator($rdi , RecursiveIteratorIterator::LEAVES_ONLY) ;
            foreach ($rii as $file)
            {
                $loader = new Twig_Loader_Filesystem(array( $file->getPath() , $dir ));

                // force auto-reload to always have the latest version of the template
                $twig = new Twig_Environment($loader, array(
                    'cache' => $cacheDir,
                    'auto_reload' => true
                ));
                $twig->addExtension(new Twig_Extensions_Extension_I18n());

                // force compilation
                if ($file->isFile() && ( in_array($file->getExtension(), array('html','htm','twig')))  ) {
                    $this->info("Loading template ". $rii->getSubPathname());
                    $twig->loadTemplate($rii->getSubpathname());
                }
            }
        }

    }

    public function run()
    {
        $this->info("Running command to parse i18n messages...");
        $scanPaths = (array) $this->config('paths');
        $output = $this->config('csv');
        $flush = $this->config('flush');

        if ( file_exists('cache/twig') ) {
            $cacheDir = 'cache/twig';
        } else {
            $cacheDir = 'cache';
        }

        if ( ! file_exists($cacheDir) ) {
            mkdir($cacheDir, 0777, true);
        }

        $this->info("Rendering templates to cache...");
        $this->renderToCache($scanPaths, $cacheDir);
        $parser = new GettextParser;

        $this->info("Parsing messages from $cacheDir...");
        $parser->parseDirectory($cacheDir);

        $this->info( $parser->count() . " messages parsed." );

        if ( $flush ) {
            $parser->outputCsv();
        } else {
            $this->info("Writing messages to csv file $output...");
            $parser->writeCsv($output);
        }
    }

}
