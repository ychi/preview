<?php

class GettextParserTest extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $parser = new Corneltek\Preview\GettextParser;
        $parser->parseFile('src/Corneltek/Preview/FileUtils.php');
        $parser->writeCsv("tests/output.csv");
    }
}

