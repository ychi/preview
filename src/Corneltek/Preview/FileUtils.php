<?php
namespace Corneltek\Preview;

class FileUtils
{
    static function rcopy($source, $dest)
    {
        if ( ! file_exists($dest) ) {
            mkdir($dest, 0755, true);
        }

        foreach (
            $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST) as $item
        ) 
        {
            $targetPath = $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                futil_mkdir_if_not_exists($targetPath, 0755, true);
            } else {
                futil_copy_if_newer($item, $targetPath);
            }
        }
    }

}


