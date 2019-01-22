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
        //$path = StaticRoboUtils::fixPath($path);
        $slashCnt = substr_count($path, '/');
        for ($i = 4; $i < $slashCnt; $i++)
        {
            $ret .= ' &nbsp; ';
        }

        return $ret;
    }

    function doDir($path)
    {
        $ret = '';
        $baseRoboUrl = preg_replace('/^.*?fragments\//', '', $path);
        $baseRoboUrl = StaticRoboUtils::fixPath($baseRoboUrl);
        //if (substr($baseRoboUrl, -1) == '/')
        //$baseRoboUrl = substr($baseRoboUrl, 0, strlen($baseRoboUrl) - 1);
        $iterator = new DirectoryIterator($path);
        foreach ($iterator as $fileinfo)
        {
            if ($fileinfo->isDir() && !$fileinfo->isDot())
            {
                $label = $fileinfo->getFilename() != '.' ? $fileinfo->getFilename() : '';
                if (substr($label, 0, 1) == '/')
                    $label = substr($label, 1);
                if (!isset($_GET['robopage']) || $_GET['robopage'] == '' || $_GET['robopage'] == null)
                {
                    $hLink = '<a href="?robopage=' . $label . '&amp;layout=nerd">' . $label . '</a><br/>';
                }
                else
                {
                    $hLink = '<a href="?robopage=' . $baseRoboUrl . '/' . $label . '&amp;layout=nerd">' . $label . '</a><br/>';
                }
                $ret .= $this->indent($path) . $hLink;
                $test = $fileinfo->getFilename();
                if (isset($test) && $test[0] != '.')
                {
                    $dbg = StaticRoboUtils::fixPath($this->doDir($path . '/' . $fileinfo->getFilename()));
                    if ($dbg != '')
                    {
                        $ret .= $dbg;
                    }
                }
            }
            $ret .= "\n";
        }
        return $ret;
    }

    function getOutput($divid)
    {
        $ret = $testUrl = '';
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

        $ret .= '<a href="' . $upUrl . '"><button name="upone" value="upone"> Up One Directory </button></a>';

        $ret .= '<p style="color: black;"> Working directory: <b>' . $currentDisplayPath . "</b></p>";
        $ret .= '<p> Subdirectories </p>';

        //echo "  .......start here mf<br/>";
        $ret .= $this->doDir($_SESSION['currentDirPath']);

        return $ret;
    }

}
?>
