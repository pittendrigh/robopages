<?php
@session_start();
include_once("plugin.php");
include_once("file.php");
include_once("conf/globals.php");


class phile extends file 
{

    function __construct()
    {
        
    }

    // file is used for boilerplate *.frag div contents mapped to  $_SESSION['currentDirPath'] . $divid . '.frag' 
    function getOutput($divid)
    {
        global $sys_home_link;
        $ret = '';

        $lclSrc = $_SESSION['currentDirPath'] . 'roboresources/' . $divid . '.frag';
        $defaultSrc = $_SESSION['prgrmDocRoot'] . 'roboresources/' . $divid . '.frag';

        if (@stat($lclSrc))
        {
            $ret = file_get_contents($lclSrc);
        }
        else if (@stat($defaultSrc))
        {
            $ret = file_get_contents($defaultSrc);
        }

	$home_link = "/";
	if(isset($sys_home_link))
		$home_link = $sys_home_link;
        $ret = str_replace("_HOME_",$home_link, $ret);
        return ($ret);
    }

}
?>
