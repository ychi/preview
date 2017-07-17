<?php
namespace Corneltek\Preview;

class HgUtils {
    static public function find_hg()
    {
        return \futil_findbin('hg');
    }

    static public function get_revision()
    {
        $hg = self::find_hg();
        $rev = null;
        $hash = null;
        if($hg) {
            return trim(shell_exec("$hg id"));
        }
        return false;
    }
}
