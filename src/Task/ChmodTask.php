<?php
namespace Corneltek\Preview\Task;

class ChmodTask extends BaseTask {

    public function run()
    {
        // for windows, we don't need chmod.
        if ( PHP_OS !== 'Windows' && PHP_OS !== 'WINNT' ) {
            $items = $this->config('paths');
            foreach( $items as $item ) {
                list($mode,$path) = $item;
                $this->info("Changing mode on {$path} to {$mode}...");
                system("chmod -R " . $mode . ' ' . $path);
            }
        }
    }
}

