<?php
@session_start();
include_once("plugin.php");


class blog extends plugin
{
    protected $indexedFiles;
    protected $hashedFiles;
    protected $blogCnt;
    protected $mimer;
    protected $blogFilesDirPath;

    public
            function __construct()
    {
        $this->init();
    }

    function init()
    {
        $this->blogFilesDirPath = $_SESSION['prgrmDocRoot'] . 'roboresources/Blog/';
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/Blog'))
            $this->blogFilesDirPath = $_SESSION['currentDirPath'] . 'roboresources/Blog/';
        parent::init();
    }

    function formatDate($str)
    {
        //echo "format: ", $str, "<br/>";

        $bits = explode("-", $str);
        $year = $bits[0];
        $month = $bits[1];
        $day = substr($bits[2], 0, 2);

        $darr = array($year, $month, $day);
        return $darr;
    }

    function mkFileLists()
    {
        $tmpArr = array();

        $fileCnt = 0;
        $fd = opendir($this->blogFilesDirPath);
        if ($fd != null)
        {
            while (($file = readdir($fd)) !== FALSE)
            {
                if ($file[0] == '.')
                {
                    continue;
                }
                $test = basename($file);
                if (strstr($test, "blog"))
                {
                    //$tmpArr[$fileCnt] = $file;
                    $tmpArr[$file] = $fileCnt;
                    $fileCnt++;
                }
            }
         }
         $this->blogCnt = $fileCnt++;
         krsort($tmpArr);
         array_reverse($tmpArr);
         $i=0;
         foreach(array_keys($tmpArr) as $ablogFile)
         { 
             $this->indexedFiles[$i] = $ablogFile;
             $this->hashedFiles[$ablogFile] = $i;
             //echo "[$i] $ablogFile <br/>";
             $i++;
         }
         
   }

    function miniMeme($date, $text)
    {
        $ret = ' 
<div class="miniMeme">
<script type="text/javascript">
protected el = document.getElementById(\'miniMeme\');
el.style.margin-left = \'-4em\';
</script>';

        $ret .= ' <p class="miniDate">' . $date . '</p>' . "\n";
        $ret .= ' <p class="miniText">' . $text . '</p>';
        $ret .= '</div>' . "\n";
        return $ret;
    }

    function getOutput($divid)
    {

        $this->mimer = new roboMimeTyper();
        $buff = $newer = $older = $robopage = '';

        $ret = '';

        if (isset($_GET['robopage']))
            $robopage = $_GET['robopage'];

        $iterations = 4; /////?????
        // do this in ctor?
        $this->mkFileLists();

        $blogStart = 0;
        if (isset($_GET['blogStart']))
        {
            $blogStart = $_GET['blogStart'];
        }
        else if (isset($_GET['blogFilename']))
        {
            $blogKey = $_GET['blogFilename'];
            //echo "blogKey: ", $blogKey, "<br/>";
            //echo "initial blogStart: ", $blogStart, "<br/>";
            $blogStart = $this->hashedFiles[$blogKey];
            //echo "from blogKey blogStart: ", $blogStart, "<br/>";
            if (!isset($blogStart) || $blogStart == null || $blogStart == '')
            {
                foreach (array_keys($this->hashedFiles) as $akey)
                {
                    if ($akey == $blogKey)
                    {
                        $val = $this->hashedFiles[$akey];
                    }
                }
                $blogStart = 0;
            }
        }

        if ($blogStart + $iterations > $this->blogCnt)
            $iterations = $this->blogCnt;

        $stopIdx = $blogStart + $iterations;


        if (isset($_GET['dbg']))
        {
            echo "this->blogCnt: ", $this->blogCnt, "<br/>";
            echo "iterations: ", $iterations, "<br/>";
            echo "blogStart: ", $blogStart, "<br/>";
            echo "stopIdx: ", $stopIdx, "<br/>";
        }


        $cnt = $blogStart;
        for ($i = $blogStart; $i < $stopIdx; $i++)
        {
            $lcl = '';
            $ablogFile = $this->indexedFiles[$i];
            $darr = $this->formatDate($ablogFile);
            $dateMeme = $this->miniMeme($darr[1] . "/" . $darr[2], $darr[0]);
            $lcl .= $dateMeme;
            $lcl .= '<div class="blogEntry">';
            $lcl .= trim(file_get_contents($this->blogFilesDirPath . $ablogFile));
            $lcl .= '</div>' . "\n";
            $cnt++;
            $buff .= $lcl;
        }

        $top = '<a class="button" href="?robopage=' . $robopage . '&blogStart=0"> Blog Start </a><br/><br/>' . "\n";
        if ($blogStart > 0)
        {
            $cnt = $cnt - (2 * $iterations);
            if ($cnt < 0)
            {
                $cnt = 0;
            }
            $newer = $top . '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $cnt . '"> Newer (move up) </a><br/>' . "\n";
        }
        if ($stopIdx < $this->blogCnt)
        {
            $older = '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $stopIdx . '"> Older (move down) </a><br/>' . "\n";
        }

        $ret = $newer . $buff . $older. $top;
        //$ret = $buff;
        return ($ret );
    }

}
?>
