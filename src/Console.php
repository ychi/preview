<?php
namespace Corneltek\Preview;
use CLIFramework\Application;

class Console extends Application
{
    const NAME = 'preview';
    const VERSION = "2.0.0";

    public function init()
    {
        parent::init();
        $this->registerCommand('render');
        $this->registerCommand('zip');
    }

    public function brief()
    {
        return 'design preview system';
    }
}
