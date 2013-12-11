<?php
namespace Corneltek\Preview\Task;
use Corneltek\ScssRunner;
use Corneltek\SassRunner;

class CompileMessageTask extends BaseTask 
{
    public function run() 
    {
        $this->info("Running command to parse i18n messages...");
        $scanPaths = $this->config('paths');

    }
}
