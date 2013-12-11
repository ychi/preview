<?php
namespace Corneltek\Preview;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class GettextParser
{

    public $messages = array();

    public function parseFile($file) {
        $content = file_get_contents($file);
        preg_match_all( '#gettext\("(.*?)"\)#usm', $content, $regs );
        if ( isset($regs[1]) ) {
            foreach( $regs[1] as $matched ) {
                $this->messages[ $matched ] = $file;
            }
        }
    }

    public function parseDirectory($dir) {
        foreach (new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir), 
            RecursiveIteratorIterator::LEAVES_ONLY) as $file)
        {
            if ( $file->isFile() ) {
                $this->parseFile($file);
            }
        }
    }

    public function getMessages()
    {
        return $this->messages;
    }

    public function outputCsv( $filename = 'messages.csv' ) {
        header( "Content-Type: application/octet-stream");
        header( "Content-Disposition: attachment;filename=$filename");
        header( "Content-Transfer-Encoding: binary" );
        $this->writeCsv("php://output");
    }

    public function writeCsv( $output = 'messages.csv' ) {
        // output message in csv format
        // chr(255) . chr(254) . mb_convert_encoding
        $fp = fopen($output,"w");
        fputs($fp, chr(255) . chr(254));
        fputs($fp, mb_convert_encoding( "Message ID\tTranslated Message\n" , 'UTF-16LE', 'UTF-8' ) );
        foreach( $this->messages as $message => $file ) {
            fputs($fp, mb_convert_encoding( $message . "\n", 'UTF-16LE', 'UTF-8'));
        }
        fclose($fp);
    }
}
