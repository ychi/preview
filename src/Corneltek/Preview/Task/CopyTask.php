<?php
namespace Corneltek\Preview\Task;
use Corneltek\Preview\FileUtils;

class CopyTask extends BaseTask
{
    public function run() {
        if ( $paths = $this->config('paths') ) {
            foreach( $paths as $path ) {
                if ( false !== strpos($path,':') ) {
                    list($src,$dest) = explode(':',$path);
                    $this->info("Copy $src -> $dest");
                    FileUtils::rcopy($src, $dest);
                } else {
                    $this->fatal('paths require src:dest format string');
                }
            }
        }
    }
}

