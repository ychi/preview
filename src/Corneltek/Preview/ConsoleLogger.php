<?php
namespace Corneltek\Preview;

class ConsoleLogger {

    public $level = 0;

    public function setVerbose()
    {
        $this->level = 1;
    }

    public function isVerbose() {
        return $this->level == 1;
    }

    public function isDebug() {
        return $this->level == 2;
    }

    public function setDebug()
    {
        $this->level = 2;
    }

    public function debug($msg)
    {
        if ( $this->level >= 2 ) {
            echo $msg, "\n";
        }
        return true;
    }

    public function info($msg) {
        if ( $this->level >= 1 ) {
            echo $msg , "\n";
        }
        return true;
    }

    public function error($msg) {
        echo $msg , "\n";
        return false;
    }

    public function fatal($msg) {
        die($msg . "\n");
    }

    static public function getInstance() {
        static $instance;
        if ( $instance )
            return $instance;
        $instance = new self;
        return $instance;
    }
}
