<?php
require 'vendor/autoload.php';
use Corneltek\Preview\Task\CompileMessageTask;
$task = new CompileMessageTask(array('paths' => 'design/design/zh_TW', 'flush' => true ));
$task->run();
