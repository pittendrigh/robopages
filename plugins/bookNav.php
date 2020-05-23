<?php
@session_start();
include_once("plugin.php");
//include_once("Link.php");
include_once("dynamicNavigation.php");
include_once("nextPrevButtons.php");

/// this is development code, it isn't usable yet _Mar_11_2020
/// this and nextPrevButtons.php
// 
class bookNav extends dynamicNavigation 
{
  protected $nextPrevButtons;
  protected $p2nFileDir;
  protected $p2nFile;
  protected $currentBookName;
  public $globalChapterLinks;

/* 
  protected function getP2NFile()
  {
    return $this->p2nFile;
  }
*/

  
  
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
          if($this->p2nFileDir == $_SESSION['prgrmDocRoot'])
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
  protected function assembleGlobalChapterLinks($linksString)
  {
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $url = 'Library/' . $this->currentBookName . '/' . $linkChunks[$i];
      $label = $linkChunks[$i];
      $link = '<a href="?robopage='.$url.'">' . $label . '</a>'; 
      $this->globalChapterLinks[] = $link;
    }
  }
  
  protected function getGlobalChapterLinks() 
  {
    $linksString = '';
    $handle = @opendir($this->p2nFileDir);
    while ($handle && ($file = @readdir($handle)) !== FALSE)
    {
      if ($file[0] == '.')
        continue;
      if(is_dir($this->p2nFileDir .  $file ))
      { 
        $linksString .= $file . ',';
      }
    }
    $this->assembleGlobalChapterLinks($linksString);
  }
  
  protected function findP2NFile($dir)
  {
     $ret = '';
     //echo "top findP2NFileDir: ", $dir, "<br/>";
     if(@stat($dir. '/p2n'))
     {
        //echo "found $dir" . '/p2n' . "<br/>";
        return $dir . '/p2n';
     }
     else if(!strstr($dir, 'fragments'))
       return ''; 
     else
     {
        $ret = $this->findP2NFile(dirname($dir));
        return $ret;
     }
  }

  protected function setP2NFile()
  {
    $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
    $this->p2nFileDir = dirname($this->p2nFile) . '/'; 
    $_SESSION['bookTop'] = str_replace($_SESSION['prgrmDocRoot'],"" ,$this->p2nFileDir);

    //echo "found p2nFileDir: ", $this->p2nFileDir, "<br/>";
    if(isset($_GET['dbg']))
    {
      //echo "currentBookName: ", $this->currentBookName, "<br/>";
      //echo "_GET['robopage']: ", $_GET['robopage'], "<br/>";
      //echo "p2nFile: ", $this->p2nFile, "<br/>";
    }
  }

  public function init()
  {
    parent::init();
    $this->setP2NFile();
    $this->nextPrevButtons = new nextPrevButtons();

        //echo "bookNav init <br/>";
        $this->linkshash = array();
        $this->fileKeys = array();
        $this->imageKeys = array();
        $this->dirKeys = array();
        $this->mimer = new roboMimeTyper();

        $this->gatherLinks();
/*
    $this->getGlobalChapterLinks();
    $this->nextPrevButtons = new nextPrevButtons();
*/
  }

  public function inBookTopDir()
  {
    $ret = FALSE;
    //echo "p2nFileDir: ", $this->p2nFileDir, "<br/>";
    if($_SESSION['currentDirPath'] == $this->p2nFileDir)
      $ret = TRUE;
/*
   echo "in top level: ";
   if($ret)
       echo " TRUE "; 
   else
       echo " FALSE "; 
   echo "<br/>";
*/

    return $ret;
  }

  public function getTOCJs()
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

  public function getOutput($divid)
  {
    $ret = $top = $bottom = '';

   // deal with ordering top level links later grep -i actionItem *php
   // $this->globalChapterLinks = array();
   // last page cookie not ready for prime time yet grep -i actionItem *php
   if(!isset($_GET['robopage']))
   {
       $tentativeUrl = '';
       if(isset($_COOKIE['lastrobopage']) && $_COOKIE['lastrobopage'] != null)
          $tentativeUrl = $_COOKIE['lastrobopage'];
       if(isset($tentativeUrl) && $tentativeUrl != null)
       {
           $_GET['robopage'] = $tentativeUrl;
       }
   }

    $top .= '<button id="tcdo" onClick="tocToggle()">toc</button>';
    $top .= $this->getTOCJs();
    $top .= $this->nextPrevButtons->getOutput('');
    $top .= '<div id="ttoc">';

    $bottom='';
    if(! $this->inBookTopDir())
    {
      $bottom .= '<div class="subnav">';
      $toc = new dynamicNavigation();
      $bottom .= $toc->getOutput('');
      $bottom .= '</div>';
    }

    $this->gatherLinks($this->p2nFileDir);
    $top .= parent::getOutput($divid);

    $ret =  $top . $bottom .  '</div>';
    return($ret);
  }
}
?>
