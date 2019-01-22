<?php
@session_start();
include_once("plugin.php");

/*
 * * Design Goals:
 * * There is only one blog /fragments/roboresources/blog/year (perhaps 2018)
 * * blog.php defaults to this year as from current date. &blogYear=2015 can change it
 * * Code still cycles through showing N blogs with earlier and later buttons,
 * * as per what is already here.
 * * Earlier button goes to end of this year? Drop down menu GETS to go to another year?
 */

class blog extends plugin
{
    protected
            $indexedFiles;
    protected
            $hashedFiles;
    protected
            $blogCnt;
    protected
            $mimer;
    protected
            $blogFilesDirPath;

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

    // not used right now...
    function formatDate($str)
    {

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
                if (strstr($file, "blog"))
                {
                    //if($file == "2015-05-31-13:39:25.blog")
                    //echo "file: ", $file, "<br/>";
                    $tmpArr[$file] = $file;
                }
            }
        }
        // get date descending order on files like 2013-06-24-20:19:44.blog
        natsort($tmpArr);
        array_reverse($tmpArr);

        $this->indexedFiles = array();
        $this->blogCnt = count($tmpArr);
        $cnt = 0;
        foreach (array_keys($tmpArr) as $ablogFile)
        {

            // include the path below to see if it's a directory?  Shouldn't be. Doesn't hurt to know
            $linkTargetType = $this->mimer->getRoboMimeType($this->blogFilesDirPath . $ablogFile);
            $this->hashedFiles[$ablogFile] = $cnt;
            //$this->indexedFiles[$cnt] = $ablogFile;
            $this->indexedFiles[$cnt] = $ablogFile;
            $cnt++;
        }
    }

    function miniMeme($date, $text)
    {
        $ret = ' 
<div class="miniMeme">
<script type="text/javascript">
var el = document.getElementById(\'miniMeme\');
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
            //$lcl .= '<h2 class="dadate">' .  str_replace(".blog","",$this->formatDate($ablogFile)) . '</h2>';
            $darr = $this->formatDate($ablogFile);
            //echo "$darr[0] <br/>"; echo "$darr[1] <br/>"; echo "$darr[2] <br/>";
            $dateMeme = $this->miniMeme($darr[1] . "/" . $darr[2], $darr[0]);
            $lcl .= $dateMeme;
            //echo "xxxxxxxxxxxxxxxxxxxx: ", htmlentities($dateMeme), "<br/>";
            $lcl .= '<div class="blogEntry">';
            $lcl .= trim(file_get_contents($this->blogFilesDirPath . $ablogFile));
            $lcl .= '</div>' . "\n";
            $cnt++;
            $buff .= $lcl;
        }

        if ($blogStart > 0)
        {
            $cnt = $cnt - (2 * $iterations);
            if ($cnt < 0)
            {
                $cnt = 0;
            }
            $newer = '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $cnt . '"> Newer (move up) </a><br/>' . "\n";
        }
        if ($stopIdx < $this->blogCnt)
        {
            $older = '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $stopIdx . '"> Older (move down) </a><br/>' . "\n";
        }

        $ret = $newer . $buff . $older;
        return ($ret );
    }

}
?>
