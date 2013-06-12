<?php
namespace Corneltek\PreviewSystem;
use SplFileInfo;

class MySplFileInfo extends SplFileInfo
{
    function getExtension()
    {
        return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
    }
}

/* Which handles path info */
class VirtualPath extends MySplFileInfo
{
    function __construct( $pathInfo )
    {
        parent::__construct( ltrim( $pathInfo ) );
    }
}

