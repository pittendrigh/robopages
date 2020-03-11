<?php
@session_start();
include_once("plugin.php");

class nextPrevButtons extends plugin 
{
  protected $p2nFile;
  protected $p2nHash;
  protected $n2pArray;
  protected $maxPageNum = 0;

  public function __construct()
  {
    $this->init();
  }

  public function setP2NFile($path)
  {
    //echo "bookNav sssetting $path <br/>";
    $this->p2nFile = $path;
  }

  protected function readP2NFile()
  {
    /**
    **  The p2n location needs a location convention.
    **  For any path underneath /fragments/Libray will be set in conf/dirlayouts.ini
    */
    //echo "<br/> should be: ", $this->p2nFile , "<br/>";
    $lines = file ($this->p2nFile);
    $this->maxPageNum = count($lines);
    $pageNum = 0;
    foreach ($lines as $aline)
    {
      $rurl = trim($aline);
      $this->p2nHash[$rurl] = $pageNum;
      $this->n2pArray[$pageNum]= $rurl;
      $pageNum++;
      if(isset($_GET['robopage']) &&  $_GET['robopage'] == $rurl)
      {
        //echo "rrrrrrrurl: " , $rurl, " (", $_GET['robopage'], ")<br/>";
        $_SESSION['currentPageNum'] = $this->p2nHash[$_GET['robopage']];
        //echo "setting currentPageNum: " . $_SESSION['currentPageNum'] . "<br/>";
      }
    }
  }

  public function getOutput($divid)
  {
    $ret = '';
    $this->readP2NFile();
    $nowNum = 0;

    if(isset($_SESSION['currentPageNum']) 
    && $_SESSION['currentPageNum'] != NULL)
      $nowNum = $_SESSION['currentPageNum'];
    $nextNum = $nowNum + 1;
    $prevNum = $nowNum - 1;

    if($nextNum > $this->maxPageNum)
      $nextNum = $this->maxPageNum;
    if($prevNum < 0)
      $prevNum = 0;

    $nextr = $this->n2pArray[intval($nextNum)];
    $prevr = $this->n2pArray[intval($prevNum)];
    $nextUrl = "?robopage=" . $nextr; 
    $prevUrl = "?robopage=" . $prevr; 
    $nowNum++;

    $ret .=  '<h4 style="margin: 0; padding: 0; border: 0; text-align: center">Page '.$nowNum.'</h4>';
    $ret .= '<p class="buttonbox">';
    $ret .=  '<a class="button" href="'.$nextUrl.'">Next</a><br/>';
    $ret .=  '<a class="button" href="'.$prevUrl.'">Prev</a><br/>';
    $ret .= '</p>';

    /*
    foreach (array_keys($this->p2nHash) as $aPath)
    {
    $ret .= $aPath . " :: " . $this->p2nHash[$aPath] . "<br/>";
    }
    */
    return($ret);
  }
}
?>