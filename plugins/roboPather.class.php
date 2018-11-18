<?php

@session_start();

class roboPather
{

    function fixROBOPATHs($str)
    {
        $ret = preg_replace("/_ROBOPATH_/", $_SESSION['currentClickDirUrl'], $str);
        return($ret);
    }

}

?>
