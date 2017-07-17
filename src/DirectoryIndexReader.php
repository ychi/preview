<?php
namespace Corneltek\Preview;

use SplFileInfo;
use DirectoryIterator;

class DirectoryIndexReader
{
    public $path;

    public $ignorePrivateFiles = true;

    public function __construct( $path )
    {
        $this->path = $path;
    }

    public function find()
    {
        $files = array();
        $iterator = new DirectoryIterator($this->path);
        foreach ($iterator as $fileinfo) {
            if( $fileinfo->isDot() )
                continue;

            $filename = $fileinfo->getFilename();
            if ( $filename[0] == '.' ) {
                continue;
            }

            if ( $this->ignorePrivateFiles && $filename[0] == '_' ) {
                continue;
            }

            # var_dump( $fileinfo->getFilename() );
            $files[] = new SplFileInfo( $fileinfo->getPathname() );
        }
        usort($files,function($a,$b) {
            if( $a->isDir() && $b->isDir() || $a->isFile() && $b->isFile() ) {
                return strcmp( $a->getFilename() , $b->getFilename() );
            }
            else {
                return $a->isFile() ? 1 : -1;
            }
        });
        return $files;
    }


    /**
     * Display the directory index.
     */
    public function display()
    {
        $files = $this->find();
        $twig = TwigEnvironmentFactory::create(array());

        putenv('PATH=/bin:/usr/bin:/usr/local/bin:/opt/local/bin');
        $hash = HgUtils::get_revision();

        $projectName = basename(getcwd());
        $pathinfo = new SplFileInfo( $this->path );

        $readme = '';
        $readmeFile = $this->path . DIRECTORY_SEPARATOR . 'README.txt';
        if ( file_exists($readmeFile) ) {
            $readme = file_get_contents($readmeFile);
        }

        $template = $twig->loadTemplate("@preview/dir.html.twig");
        $template->display( array(
            'Hash' => $hash,
            'Files' => $files,
            'Readme' => $readme,
            'ProjectName' => $projectName,
            'CurrentPath' => $this->path,
            'ParentPath'  => $pathinfo->getPath(),
            'PhpVersion' => phpversion(),
        ));
    }
}
