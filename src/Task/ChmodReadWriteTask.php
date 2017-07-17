<?php
namespace Corneltek\Preview\Task;

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

