<?php
include_once("imgResizer.php");
include_once("plugin.php");

class mkSlides extends plugin
{

    function is_image($file)
    {
        $ret = FALSE;
        if (stristr($file, '.jpg') || stristr($file, '.jpeg') || stristr($file, '.gif') || stristr($file, '.png')
        )
        {
            $ret = TRUE;
        }

        return $ret;
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

    function getOutput($mess)
    {
        $ret = '';
        $imgPaths = $this->getImageFilenames($_SESSION['currentDirPath']);
        @mkdir($_SESSION['currentDirPath'] . 'roboresources/slideshow');
        $imgResizer = new imgResizer();
        $inDir = $_SESSION['currentDirPath'];
        $destDir = $_SESSION['currentDirPath'] . 'roboresources/slideshow/';
        foreach ($imgPaths as $aPath)
        {
            //echo $aPath. '<br/>';
            $inBasename = $aPath;
            $inPath = $inDir . $aPath;
            $outBasename = $inBasename;
            $outPath = $destDir . $outBasename;
            // destroy dir slideshow...........:
            //echo "doIt($inPath, $outPath, 700) <br/>"; 
            $imgResizer->doIt($inPath, $outPath, 700);
        }

        return $ret;
    }

}
?>
