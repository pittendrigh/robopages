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
        //echo $str, "<br/>";
        $darr = array();
        //$bits = explode("_", $str);
        //$darr[0] = preg_replace(":_|-:"," ", $bits[0]);
        //$darr[1] = str_replace(".blog","", $bits[1]);
/*
        $date = $bits[1];
        $year = $bits[0];
        $month = $bits[1];
        $day = substr($bits[2], 0, 2);

        $darr = array($year, $month, $day);
*/
        //return $darr;
      return ($str);
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
                    //echo $file, "<br/>";
                    $tmpArr[$file] = $fileCnt;
                    $fileCnt++;
                }
            }
         }
         $this->blogCnt = $fileCnt++;
         krsort($tmpArr);
         $i=0;
         foreach(array_keys($tmpArr) as $ablogFile)
         { 
             //echo $i, " " ,  $ablogFile , "<br/>";
             $this->indexedFiles[$i] = $ablogFile;
             $this->hashedFiles[$ablogFile] = $i;
             $i++;
         }
         
   }

    function miniMeme($text, $date)
    {
        $ret = '';
        $ret .= '<p><span class="miniText">' . $text . '</span>' . "\n";
        $ret .= '<span class="miniDate">' . $date . '</span></p>';
        return $ret;
    }

    function getOutput($divid)
    {   
        global $sys_blog_title;
        $blogtitle = 'Blog';
        if(isset($sys_blog_title) && $sys_blog_title != null){
            $blogtitle = $sys_blog_title;
        }
       

        $ret = '<div class="blog">';
        $ret .= '<h2> '.$blogtitle.' </h2>';
        $ret .= $this->ggetOutput('');
        $ret .= '</div>';
        return ($ret);
    }

    function ggetOutput($divid)
    {

        $this->mimer = new roboMimeTyper();
        $buff = $newer = $older = $robopage = '';

        if (isset($_GET['robopage']))
            $robopage = $_GET['robopage'];
        if(!isset($_GET['robopage']) || $_GET['robopage'] == null)
          $robopage = '';

        $iterations = 8; /////????? class contant?
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
            if(isset($this->indexedFiles[$i]) && $this->indexedFiles[$i] != null)
            {
              $ablogFile = $this->indexedFiles[$i];
              $ablogFileLabel = preg_replace("/:*/","",$this->indexedFiles[$i]);
            }
            //$darr = $this->formatDate($ablogFile);
            //$dateMeme = $this->miniMeme($darr[0], $darr[1]);
            //$dateMeme = $this->miniMeme($ablogFile, '');
            //$dateMeme = "<h4> ".str_replace(".blog","",$ablogFile)." </h4>";
            $dateMeme = "<h4> ".str_replace(".blog","",$ablogFileLabel)." </h4>";

            $lcl .= $dateMeme;
            //$lcl .= '<div class="blogEntry">';
            $lcl .= trim(file_get_contents($this->blogFilesDirPath.$ablogFile));
            //$lcl .= '</div>' . "\n";
            $cnt++;
            $buff .= $lcl;
        }

       
        if(isset($robopage) && $robopage != null) 
         $top='<a class="button" href="?robopage='.$robopage.'&blogStart=0"> Blog Start</a><br/><br/>' ."\n";
         else 
         $top='<a class="button" href="?blogStart=0"> Blog Start</a><br/><br/>' ."\n";
         
        if ($blogStart > 0)
        {
            $cnt = $cnt - (2 * $iterations);
            if ($cnt < 0)
            {
                $cnt = 0;
            }
            $newer = $top . '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $cnt . '"> Newer  </a><br/>' . "\n";
        }
        if ($stopIdx < $this->blogCnt)
        {
           if(isset($robopage) && $robopage != null)
            $older = '<a class="button" href="?robopage=' . $robopage . '&blogStart=' . $stopIdx . '"> Older <br/> </a>' . "\n";
           else
            $older = '<a class="button" href="?blogStart=' . $stopIdx . '"> Older <br/>  </a>' . "\n";
        }

        //$ret = $blogbanner. $newer . $buff . $older. $top;
        $ret = $newer . $buff . $older. $top;
        //$ret = $buff;
        return ($ret );
    }

}
?>
