<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

class dirChanger extends plugin
{

    function indent($path)
    {
        $ret = '';
        $path = StaticRoboUtils::fixPath($path);
        $path = preg_replace("/^.*fragments\//","",$path);        
        $slashCnt = substr_count($path, '/');
    // if(strstr($path,'Conservation') || strstr($path,'Kestrels'))
     //   echo $slashCnt, " :: ", $path, "<br/>";

        for ($i = 0; $i < $slashCnt; $i++)
        {
            $ret .= ' &nbsp; &nbsp; ';
        }

        return $ret;
    }

    function doDir($path)
    {
        $ret = '';
        $baseRoboUrl = preg_replace('/^.*?fragments\//', '', $path);
        $baseRoboUrl = StaticRoboUtils::fixPath($baseRoboUrl);

        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo)
        {
            if ($fileinfo->isDir() && !$fileinfo->isDot())
            {
                $hLink='';
                $label = $fileinfo->getFilename();
                $newPath = StaticRoboUtils::fixPath($path. '/' . $label);
                if (substr($label, 0, 1) == '/')
                    $label = substr($label, 1);
                 if(strstr($baseRoboUrl . $label,"roboresources"))
                 {
                    $hLink = '<a href="?robopage=' . $baseRoboUrl . '/' . $label 
                         . '&amp;layout=nerd"><span class="smallfont system">' . $label . '</span></a><br/>';
                 }
                 else
                 {
                    $hLink = '<a href="?robopage=' . $baseRoboUrl . '/' . $label . '&amp;layout=nerd">' . $label . '</a><br/>';
                 }
                $ret .= $this->indent($newPath);
                $ret .= $hLink;
                $ret .= $this->doDir($newPath);
            }
            $ret .= "\n";
        }
        return $ret;
    }

    function getOutput($divid)
    {
        $ret = '<div class="dirChanger">';
        $testUrl = '';
        $upUrl = '?layout=nerd';
        if (isset($_GET['robopage']) && $_GET['robopage'] != '')
        {
            $testUrl = dirname($_GET['robopage']);
            if ($testUrl[0] == '.')
                $testUrl = null;
        }

        if (isset($testUrl) && $testUrl != null)
            $upUrl = '?robopage=' . $testUrl . '&amp;layout=nerd';

        //echo "prgrmDocRoot: ", $_SESSION['prgrmDocRoot'], "<br/>";
        $regx = ':' . $_SESSION['prgrmDocRoot'] . ':';
        $currentDisplayPath = preg_replace("$regx", '', $_SESSION['currentDirPath']);
        $currentDisplayPath = StaticRoboUtils::fixPath($currentDisplayPath);
        //echo "currentDisplayPath: ", $currentDisplayPath, "<br/>";

        $ret .= '<a class="button" href="' . $upUrl . '"> ../Up One Directory </a><br/>';

        $startPath = $_SESSION['prgrmDocRoot'];
        if(isset($_SESSION['currentDirUrl']))
         $startPath  .= $_SESSION['currentDirUrl'];
        $ret .= $this->doDir($startPath);

        return $ret . '</div>';
    }

}
?>
