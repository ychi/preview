<?php
namespace Corneltek\Preview;

class TemplateFile {

    public $twig;
    public $file;

    public function __construct($file, $twig) {
        $this->file = $file;
        $this->twig = $twig;
    }

    public function getDir()
    {
        return $this->file->getPath();
    }

    public function render($data = array() ) {
        $template = $this->twig->loadTemplate( $this->file->getFilename() );
        return $template->render($data);
    }

}
