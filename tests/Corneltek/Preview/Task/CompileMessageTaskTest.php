<?php

class CompileMessageTaskTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $task = new Corneltek\Preview\Task\CompileMessageTask(array(
            'paths' => 'design',
            'csv' => 'messages.csv'
        ));
        ok($task);
    }
}

