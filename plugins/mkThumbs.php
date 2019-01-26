<?php

//include_once("dynamicNavigation.php");
include_once("plugin.php");

if (@stat("../conf/globals.php"))
    include_once("conf/globals.php");

class mkThumbs extends plugin
{

    function __construct()
    {
        @mkdir($_SESSION['currentDirPath'] . 'roboresources', 0755);
        @mkdir($_SESSION['currentDirPath'] . 'roboresources/pics', 0755);
        @mkdir($_SESSION['currentDirPath'] . 'roboresources/thumbs', 0755);
    }


    function getImageFilenames($dir)
    {

        $cnt = 0;
        $ret = '';
        $slidePaths = array();

        if ($dir_handle = opendir($dir))
        {
            while (($file = readdir($dir_handle)) != false)
            {
                if ($file != '.' && $file != '..')
                {
                    if ($this->is_image($file))
                    {
                        $slidePaths[$cnt] = $file;
                        $cnt++;
                    }
                }
            }
        }
        closedir($dir_handle);
        sort($slidePaths, SORT_NATURAL | SORT_FLAG_CASE);

        return $slidePaths;
    }

    // should be using roboMimeTyper? Why?  grep -i actionitem *php
    function is_image($file)
    {
        $ret = FALSE;
        if (stristr($file, '.jpg') || stristr($file, '.jpeg') || stristr($file, '.gif') || stristr($file, '.png'))
        {
            $ret = TRUE;
        }

        return $ret;
    }

    //function make_thumb($src, $dest, $desired_width) ....might be useful too
    // this is for use in the dashboard lights RobopageAdmin.php menuig system
    function make_thumbs($width)
    {
        $ret = '';
        $imgPaths = $this->getImageFilenames($_SESSION['currentDirPath']);
        $imgResizer = new imgResizer();
        $inDir = $_SESSION['currentDirPath'];
        $destDir = $_SESSION['currentDirPath'] . 'roboresources/thumbs/';
        foreach ($imgPaths as $aPath)
        {
            $inBasename = $aPath;
            $inPath = StaticRoboUtils::fixPath($inDir . $aPath);
            $outBasename = 'tn-' . $inBasename;
            $outPath = StaticRoboUtils::fixPath($destDir . $outBasename);
            // destroy dir slideshow...........:
            $imgResizer->doIt($inPath, $outPath, $width);
            $outUrl = 'fragments/roboresources/thumbs/' . str_replace($_SESSION['prgrmDocRoot'],'',$_SESSION['currentDirPath']) . $outBasename;
            $ret .= '<img src="'.$outUrl.'" alt="'.$outBasename.'"/> ';
            @symlink($_SESSION['currentDirPath'] . $inPath, $_SESSION['currentDirPath'] . 'roboresources/pics/' . $inPath);
        }

        return $ret;
    }

    function getOutput($x)
    {
      global $sys_width;
      $ret='';

      if(isset($sys_width) && $sys_width != null)
          $width = $sys_width;
      else
          $width = 100;

      $ret='';
      $ret .= $this->make_thumbs($width);
      return $ret;
    }
}
