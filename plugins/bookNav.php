<?php
@session_start();
include_once("plugin.php");
//include_once("Link.php");
include_once("dynamicNavigation.php");
include_once("nextPrevButtons.php");

/// this is development code, it isn't usable yet _Mar_11_2020
/// this and nextPrevButtons.php
///
class bookNav extends dynamicNavigation 
{
  protected $nextPrevButtons;
  protected $p2nFileDir;
  protected $p2nFile;
  protected $currentBookName;
  public $globalChapterLinks;
 
  protected function getP2NFile()
  {
    return $this->p2nFile;
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
  
  /*
  This code is executing now so we know we're in a book mode
  having a (so far) hard-coded dependency on a p2nFile
  We'll also assume a hard-coded dependency on all
  books as  ...../fragments/Library/Thisbook or ..../fragments/Library/Thatbook
  and p2nFile as ..../fragments/Library/Thatbook/p2n
  */

  protected function findP2NFile($dir)
  {
     $ret = '';
     if(@stat($dir. '/p2n'))
        return $dir;
     if(!strstr($dir, 'fragments'))
       return ''; 
     else
        $ret = $this->findP2NFile(dirname($dir));
     return $ret;
  }

  protected function setP2NFile()
  {
    $this->p2nFileDir = $this->findP2NFile($_SESSION['currentDirPath']);
    $this->p2nFile = $this->p2nFileDir . '/p2n'; 
    $_SESSION['bookTop'] = str_replace($_SESSION['prgrmDocRoot'],"" ,$this->p2nFileDir);
    $this->currentDirPath = $this->p2nFileDir;


    if(isset($_GET['dbg']))
    {
      echo "currentBookName: ", $this->currentBookName, "<br/>";
      echo "_GET['robopage']: ", $_GET['robopage'], "<br/>";
      echo "p2nFile: ", $this->p2nFile, "<br/>";
    }
  }

  public function init()
  {
    parent::init();
    $this->setP2NFile();
    $this->nextPrevButtons = new nextPrevButtons();
    $this->nextPrevButtons->setP2NFile($this->p2nFile);

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

  public function notInTopLevelChapter()
  {
    $ret = TRUE;
    $leftPath = str_replace($_SESSION['prgrmDocRoot'], '', dirname($this->p2nFile)) . '/';
    $rightPath = $_SESSION['currentDirUrl'];
    // better get $_SESSION['p2nFile'] set properly!
    //echo $leftPath . ' || ' . $rightPath . " <br/>";
    if($leftPath == $rightPath)
      $ret = FALSE;
    return $ret;
  }

  public function getOutput($divid)
  {
    $this->globalChapterLinks = array();
    $ret = '';

    //$ret .= '<a class="button" href="'.$_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'] . '&amp;layout=galleryMode"' . '>gallery view</a><br/>';
    $ret .= $this->nextPrevButtons->getOutput('');

    $ret .= parent::getOutput($divid);

/* following get something to show quickly development code, likely go away soon
    if(@stat($_SESSION['prgrmDocRoot'] . 'roboresources/chaptersLinksList.frag'  ))
    { 
       $chaptersFragger = new file();
       $ret .= $chaptersFragger->getOutput('chaptersLinksList');
    }
    else
    {
       $this->getGlobalChapterLinks();
       $this->nextPrevButtons->setP2NFile($this->p2nFile);
       $cnt = count($this->globalChapterLinks) -1;
       for($i=0; $i<$cnt; $i++)
       {
         $ret .= $this->globalChapterLinks[$i];
       }
    }
*/

    if($this->notInTopLevelChapter())
    {
      // put this hard-coded div into bookNav.xml? grep -i actionItem *php
      $ret .= '<div class="subnav">';
      $toc = new dynamicNavigation();
      $ret .= $toc->getOutput('');
      $ret .= '</div>';
    }

    return($ret);
  }
}
?>
