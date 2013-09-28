<?php
namespace Corneltek\Preview\Task;
use Corneltek\Preview\FileUtils;

class CopyDesignTask extends BaseTask
{
    public function run()
    {
        $this->info("Copying design files to preview directory...");
        $templateDir = $this->config('TemplateDir');
        $previewDir = $this->config('PreviewDir');

        if ( ! $templateDir || ! $previewDir ) {
            return $this->fatal("TemplateDir and PreviewDir required.");
        }

        if ( $this->config('rsync') ) {
            system("rsync -r $templateDir/ $previewDir/");
        } else {
            FileUtils::rcopy($templateDir, $previewDir);
        }
    }
}

