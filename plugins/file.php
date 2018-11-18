<?php

@session_start();
include_once("plugin.php");
include_once("processBackTics.class.php");

class file extends plugin
{

    function __construct()
    {
        
    }

    // file is used for boilerplate *.frag div contents mapped to  $_SESSION['currentDirPath'] . $divid . '.frag' 
    function getOutput($divid)
    {

        $ret = '';

        $lclSrc = $_SESSION['currentDirPath'] . 'roboresources/' . $divid . '.frag';
        $defaultSrc = $_SESSION['prgrmDocRoot'] . 'roboresources/' . $divid . '.frag';

        if (@stat($lclSrc))
        {
            $ret = file_get_contents($lclSrc);
        } else if (@stat($defaultSrc))
        {
            $ret = file_get_contents($defaultSrc);
        }

//echo htmlentities($ret), "<br/>";

        if (strstr($ret, '`'))
        {
            $backTicker = new processBackTics();
            $ret = $backTicker->evalBackTics($ret);
        }

        return ($ret);
    }

}

/*
  RE> processBackTics() above.
  a default boilerplate fragment file like
  ....../fragments/roboresources/bottombannerlinks.frag
  might have:
  <a href="?layout=contactus"> Contact - </a>
  but that URL will only work if Robopages installed inside the server DOCUMENT_ROOT
  If you want to install Robopages in a deeply nested directory you might want to use backtics
  <a href="`echo $_SESSION['prgramDocRoot']'`?layout=contactus"> Contact - </a>
  will put the missing part of the nested path in the code above, in front of the quesion mark
  The same code trick can be placed inside ...path/path/path/whatever.htm fragment files,
  inside img src= paths, so *.htm files can be moved up and down the directory tree,
  and still work.
 */
?>
