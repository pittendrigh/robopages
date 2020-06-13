<?php
@session_start();
include_once("plugin.php");
include_once("nextPrevButtons.php");
include_once("roboMimeTyper.php");
include_once("dynamicNavigation.php");

class bookNav extends plugin 
{
  protected $nextPrevButtons;
  protected $p2nFileDir;
  protected $p2nFile;
  protected $currentBookName;
  protected $mimer;
  public $globalChapterLinks;

  function _construct()
  {
     $this->mimer = new roboMimeTyper();
  }

  function mkLink($line,$label)
  {
    $link = '';
    //$label = basename($label);
    $linkTargetType = $this->mimer->getRoboMimeType($line);    
      //echo "mkLink line: ", $line, " ", $label, " " , $linkTargetType  , "<br/>";
      
    switch($linkTargetType)
    {
      case "link":
        $link = '<a target="_blank" href="' .  $_SESSION['currentClickDirUrl'] . basename($line) . '">' . $label. '</a>'; 
      break;
      default:
      if(isset($_GET['robopage']) && $line == $_GET['robopage'])
      { 
        $link = '<a class="highlighted" href="?robopage='.$line.'">' . $label . '</a>'."\n";
      }
      else
      {
        $thisDir = basename($_SESSION['currentDirPath']);
        //echo $thisDir, " in " , $line, "???<br/>";
        //if(strstr($line, $thisDir))
        // $link = '<a class="highlighted" href="?robopage='.$line.'">' . $label . '</a>' . "\n";
        //else
         $link = '<a href="?robopage='.$line.'">' . $label . '</a>' . "\n";
      }
    } 

    return($link);
  }

  function assembleGlobalChapterLinks($linksString)
  {
    //echo "linksString: ", $linksString, "<br/>";
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $url = $this->currentBookName . '/' . $linkChunks[$i];
     
       if(is_dir($this->p2nFileDir . trim($linkChunks[$i])))
           $label = ' <i class="material-icons" style="font-size: 80%; ">folder</i> ' .  $linkChunks[$i];
       else
            $label = $linkChunks[$i];
      //$link = '<a href="?robopage='.$url.'">' . $label . '</a>';
      //echo "assembleGlobalChapterLinks: ", $url, " ", $label, "<br/>";
      $link = $this->mkLink($url, $label);
      $this->globalChapterLinks[] = $link;
    }
  }

  function assembleLocalPageLinks($linksString)
  {
    $returningLeafLinks = array();
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $url = $this->currentBookName . '/' . $linkChunks[$i];
    
      // need to support optional label option in link line 
      //$label = basename($linkChunks[$i]);
      $label = $linkChunks[$i];
      if(isset($_GET['robopage']) && $_GET['robopage'] == $url)
          $link = '<a class="highlighted" href="?robopage='.$url.'">' . $label . '</a>';
       else
          $link = '<a href="?robopage='.$url.'">' . $label . '</a>';
      $returningLeafLinks[] = $link;
    }

    return($returningLeafLinks);
  }

  function getGlobalChapterLinks()
  {
    $linksString = '';
    //echo "p2nFile: ", $this->p2nFile, "<br/>";
    $lines = file($this->p2nFile);
    $p2nLineCnt = count($lines);
    for($i=0; $i<$p2nLineCnt; $i++)
    {
      $line = trim($lines[$i]);
      $tentativeDirPath = trim($this->p2nFileDir) .  trim($line);
       //echo $line, "<br/>";
      // top level directories are chapter names
      // we also want any leaf level *.htm files in the bookTop directory
      if(!strstr($line,'/'))
      {
          $linksString .= $line . ',';
      }
    }

    $this->assembleGlobalChapterLinks($linksString);
  }

  function subPathIsLeaf($path)
  {
    $ret=FALSE;
    // this could work in numerous ways.
    // Right now (anyway) when layout is roboBook
    // chapters contain *.htm files and other subdirectories
    // This could change.  But for now, if we strstr for *.htm
    // then we have what we need.
    if(strstr($path,'.htm'))
     $ret = TRUE;

    return($ret);
  }

  function getLocalPageLinks()
  {
    $linksString = $chaptername = '';
    $lines = file($this->p2nFile);
    $p2nLineCnt = count($lines);
    

    $robopage = '';
    if(isset($_GET['robopage']))
    {
         $robopage = $_GET['robopage'];
         if(!is_dir($_SESSION['prgrmDocRoot'] . $robopage))
           $robopage = dirname($robopage) . '/';
    }
    $chapterName = str_replace($this->p2nFileDir, "", $_SESSION['prgrmDocRoot'] . $robopage);

    for($i=0; $i<$p2nLineCnt; $i++)
    {
      $line = trim($lines[$i]);

      // top level directories are chapter names
      // we also want any leaf level *.htm files in the bookTop directory
      if(strstr($line,'/') && $this->subPathIsLeaf($line) && strstr($line,$chapterName))
      {
          $linksString .= $line . ',';
      }
    }

    return($this->assembleLocalPageLinks($linksString));
  }


  function findP2NFile($dir)
  {
     $dir = trim($dir);
     $ret = '';
     if(@stat($dir. '/p2n'))
     {
        return $dir . '/p2n';
     }
     else if(!strstr($dir, 'fragments'))
     {
       echo "no p2n file found<br/>";
       echo "redirect to an error page<br/>";
       exit;
       return '';
     }
     else
     {
        $ret = trim($this->findP2NFile(dirname($dir)));
        return $ret;
     }
  }

  function setP2NFile()
  {
    $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
    $this->p2nFileDir = dirname($this->p2nFile) . '/';
    $this->currentBookName = preg_replace(":\/$:",'',str_replace($_SESSION['prgrmDocRoot'],"" ,$this->p2nFileDir));
    $_SESSION['bookTop'] = $this->currentBookName; 

  }

  function init()
  {
    $this->setP2NFile();
    $this->nextPrevButtons = new nextPrevButtons();

    $this->linkshash = array();
    $this->fileKeys = array();
    $this->imageKeys = array();
    $this->dirKeys = array();
    $this->mimer = new roboMimeTyper();

    //$this->gatherLinks();
  }

  function inBookTopDir()
  {
    $ret = FALSE;
    if($_SESSION['currentDirPath'] == $this->p2nFileDir)
      $ret = TRUE;

    return $ret;
  }

  function getTOCJs()
  {
    $ret = '';
    $ret .= <<<ENDO
<script>
    function tocToggle()
    {
      var x = document.getElementById("ttoc");
      var b = document.getElementById("tcdo");
      if (x.style.display === "none")
      {
        x.style.display = "block";
        b.innerHTML="toc";
      }
      else
      {
        x.style.display = "none";
        b.innerHTML="TOC";
      }
     }
</script>
ENDO;

    return $ret;
  }


  function getDirlinksPath()
  {
     $ret = $this->p2nFile;
     return($ret);
  }

  function getLines($path)
  {
     $lines = $allLines = array();
     $allLines = file($path);
     $lineCnt = count($allLines);
     for($i=0; $i<$lineCnt; $i++)
     {
       if(strstr($allLines[$i], $_GET['robopage']))
       {
         $lines[] = $allLines[$i]; 
       }
     }
     return($lines);
  }

  function getOutput($divid)
  {
    $ret = $top = $bottom = '';

    $top .= '<button id="tcdo" onClick="tocToggle()">toc</button>';
    $top .= $this->getTOCJs();
    $top .= $this->nextPrevButtons->getOutput('');
    $top .= '<div id="ttoc">';

    $this->getGlobalChapterLinks();
    $cnt = count($this->globalChapterLinks);
    for($i=0; $i<$cnt; $i++)
    {
       $top .= $this->globalChapterLinks[$i];
    }

    if(!$this->inBookTopDir())
    {
      $bottom .= "<hr/>";
      $localLinksArray = $this->getLocalPageLinks();
      $cnt = count($localLinksArray);
      for($i=0; $i<$cnt; $i++)
      {
        //echo htmlentities($localLinksArray[$i]), "<br/>";
        $bottom .= $localLinksArray[$i];
      }

    }


    $ret =  $top . $bottom . '</div>';
    return($ret);
  }
}
?>
