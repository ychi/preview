<?php
namespace Corneltek\Preview\Task;

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



