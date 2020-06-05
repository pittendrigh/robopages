<?php
@session_start();
include_once("plugin.php");
include_once("dynamicNavigation.php");
include_once("nextPrevButtons.php");

class bookNav extends dynamicNavigation
{
  protected $nextPrevButtons;
  protected $p2nFileDir;
  protected $p2nFile;
  protected $currentBookName;
  public $globalChapterLinks;

  function gatherLinks($lookWhichDir=null)
  {
        $this->linkshash = $this->fileKeys = $this->imageKeys = $this->dirKeys = null;
        $this->linkshash = array();
        $this->fileKeys = array();
        $this->imageKeys = array();
        $this->dirKeys = array();

        if(isset($lookWhichDir) && $lookWhichDir != null)
        {
          $this->currentDirPath = $lookWhichDir;
          if($this->p2nFileDir == trim($_SESSION['prgrmDocRoot']))
              $this->currentDirUrl = '';
          else
              $this->currentDirUrl = str_replace($_SESSION['prgrmDocRoot'],'',$this->p2nFileDir);
        }
        if(isset($lookWhichDir) && $lookWhichDir != null)
        {
          $this->currentClickDirPath = 'fragments/' . $lookWhichDir;
          if($this->p2nFileDir == $_SESSION['prgrmDocRoot'])
              $this->currentClickDirUrl = 'fragments/';
          else
              $this->currentClickDirUrl = 'fragments/' .str_replace($_SESSION['prgrmDocRoot'],'',$this->p2nFileDir);
        }

        $this->read_dirlinks_file();
        $this->find_additional_filenames();
    }

  function assembleGlobalChapterLinks($linksString)
  {
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $url = $this->currentBookName . '/' . $linkChunks[$i];
     
       if(is_dir($this->p2nFileDir . trim($linkChunks[$i])))
           $label = ' <i class="material-icons" style="font-size: 80%; ">folder</i> ' .  $linkChunks[$i];
       else
            $label = $linkChunks[$i];
      $link = '<a href="?robopage='.$url.'">' . $label . '</a>' . "\n";
      $this->globalChapterLinks[] = $link;
    }
  }

  function getGlobalChapterLinks()
  {
    $linksString = '';
    $lines = file($this->p2nFile);
    $p2nLineCnt = count($lines);
    for($i=0; $i<$p2nLineCnt; $i++)
    {
      $line = trim($lines[$i]);
      $tentativeDirPath = trim($this->p2nFileDir) .  trim($line);

      // top level directories are chapter names
      // we also want any leaf level *.htm files in the bookTop directory
      if(!strstr($line,'/'))
      {
          $linksString .= $line . ',';
      }
    }

    $this->assembleGlobalChapterLinks($linksString);
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
       return '';
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

    if(!$this->inBookTopDir())
    {
      $bottom .= '<div class="subnav">';
      $toc = new dynamicNavigation();
      $dbg = $toc->getOutput('');
      $bottom .= $dbg;
      $bottom .= '</div>';
    }
    else{
     

    }

    $this->getGlobalChapterLinks();
    $cnt = count($this->globalChapterLinks);
    for($i=0; $i<$cnt; $i++)
    {
       $top .= $this->globalChapterLinks[$i];
    }

    $ret =  $top . $bottom . '</div>';
    return($ret);
  }
}
?>
