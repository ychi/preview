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

    public function run()
    {
        $this->info("Running command to parse i18n messages...");
        $cacheDir = $this->config('cache');
        $output = $this->config('csv');
        $flush = $this->config('flush');

        if ( ! $cacheDir ) {
            if ( file_exists('cache/twig') ) {
                $cacheDir = 'cache/twig';
            } else {
                $cacheDir = 'cache';
            }
        }

        if ( ! file_exists($cacheDir) ) {
            $this->error($cacheDir . " does not exists. twig.cache must be enabled.");
            return false;
        }

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
