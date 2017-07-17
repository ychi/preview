<?php
namespace Corneltek\Preview\Command;
use CLIFramework\Command;
use Corneltek\Preview\TaskRunner;
use Corneltek\Preview\ConfigLoader;

class RenderCommand extends Command
{
    public function brief() {
        return 'Render templates';
    }

    public function options($opts)
    {
        $opts->add('c|config:', 'The config file which should be used.');
    }

    public function execute() {
        define('ENABLE_JAVA_I18N_FILTER', true);
        $configFile = $this->options->config ?: 'config/preview.yml';
        $config = new ConfigLoader;
        $config->loadFileIfExists($configFile);
        $runner = new TaskRunner($config->stash);
        $runner->run();
        $this->logger->info("Done");
    }
}
