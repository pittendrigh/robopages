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
  public $p2nFile;
  protected $currentBookName;
  public $globalChapterLinks;
  
/*  public function __construct()
  {
    $this->globalChapterLinks = array();
    $this->init();
  }
*/
  
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
  protected function setP2NFile()
  {
    $patt1 = $_SESSION['prgrmDocRoot'] . 'Library/'; 
    //echo "ppppatt1: ", $patt1, "<br/>";
    //echo "currentDirPath: ", $_SESSION['currentDirPath'], "<br/>";
    $patt2 = str_replace($patt1, '', $_SESSION['currentDirPath']);
    //echo "pppatt2: ", $patt2, "<br/><br/>";
    $this->currentBookName = preg_replace(":/.*$:","",$patt2); 
    $this->p2nFileDir = $_SESSION['prgrmDocRoot'] . 'Library/' . $this->currentBookName . '/';
    $this->p2nFile = $this->p2nFileDir  . 'p2n' ;

    $this->currentDirPath = $this->p2nFileDir;
    $this->currentDirUrl = 'Library/' . $this->currentBookName . '/';
    $this->currentClickDirUrl = 'fragments/Library/' . $this->currentBookName . '/';


    if(isset($_GET['dbg']))
    {
      echo "currentBookName: ", $this->currentBookName, "<br/>";
      echo "_GET['robopage']: ", $_GET['robopage'], "<br/>";
      echo "currentDirPath: ", $_SESSION['currentDirPath'], "<br/>";
      echo "currentDirUrl: ", $_SESSION['currentDirUrl'], "<br/>";
      echo "patt1: ", $patt1, "<br/>";
      echo "patt2: ", $patt2, "<br/>";
      echo "p2nFileDir: ", $this->p2nFileDir, "<br/>";
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
