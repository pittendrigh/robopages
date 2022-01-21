<?php
@session_start();
include_once("plugin.php");
include_once("LinkedList.php");
include_once("roboMimeTyper.php");
include_once("p2nHandler.php");

class nextPrevButtons extends plugin
{
 protected $mimer;
 protected $p2nHandler; 

function __construct()
{
  $this->mimer = new RoboMimeTyper();
  $this->p2nHandler=new p2nHandler();
}



function getNextCookieJS()
{
 $ret = <<<ENDO
<script>
function clickNext()
{ 
 var value = document.getElementById("nextPageButton").getAttribute("href").replace("?robopage=",'');
 var cookie="lastRobopage=" + value +";max-age=1296000;" ;
 document.cookie=cookie;
}
</script>
ENDO;

return($ret);
}

function getPrevCookieJS()
{
 $ret = <<<ENDO
<script>
function clickPrev()
{ 
 var value = document.getElementById("prevPageButton").getAttribute("href").replace("?robopage=",'');
 var cookie="lastRobopage=" + value +";max-age=1296000;" ;
 document.cookie=cookie;
}
</script>
ENDO;

return($ret);
}

function getOutput($divid)
{
  $ret = '';
  $ret .= $this->getNextCookieJS();
  $ret .= $this->getPrevCookieJS();
  //$this->p2nHandler->u2pDbg();
  $nowNum = 1; // set a default?

 $robopage = '';
 if(isset($_GET['robopage']))
 {
    $robopage = $_GET['robopage'];
 }

 if($robopage == '')
 {
     $nowNum = 1;
     $nowNode = reset($this->p2nHandler->pageNum2NodeHash);
 }
 else
{
     //echo "count this->p2nHandler->orderedP2NUrls: ", count($this->p2nHandler->orderedP2NUrls), "<br/>";
     $nowNode = $this->p2nHandler->orderedP2NUrls[0];
    
     if(isset($this->p2nHandler->url2PageNodeHash[$robopage]))
     {
       $nowNode = $this->p2nHandler->url2PageNodeHash[$robopage];
       //echo "found ", $nowNode->idx, " " , $nowNode->dataObj, " on ", $robopage, "<br/>"; 
     }
     else{
       $nowNode = $this->p2nHandler->orderedP2NUrls[0];
     }
     $nowNum = $nowNode->idx;
     //$nowNode->nodeDbg();
 }

 // robopages accepts ?robopage=somdir as a url by finding a default display $_SESSION['currentDisplay']
 // if next buttons accept empty dir urls then next and prev will occasionally show same page twice
 if(@is_dir($_SESSION['prgrmDocRoot'] . $nowNode->dataObj) && $nowNode->next != null)
    $nowNode = $nowNode->next;

  //$nowNode->nodeDbg();

  $nextNode = $prevNode = $nowNode;
  $nextUrl = $prevUrl = $nowNode->dataObj;

  if($nowNode->next != null)
  {
    $nextNode = $nowNode->next;
    $nextUrl = $nextNode->dataObj;
  } 
  else
  {
    $nextNode = $nowNode;
    $nextUrl = $nowNode->dataObj;
  } 
  if(isset($nowNode->prev) && $nowNode->prev != null)
  {
    $prevNode = $nowNode->prev;
    if(@is_dir($_SESSION['prgrmDocRoot'] . $prevNode->dataObj))
    {
      $prevNode = $prevNode->prev; 
    }
    $prevUrl = $prevNode->dataObj;
  } 
  else
  {
    $prevNode = $nowNode;
    $prevUrl = $nowNode->dataObj;
  } 

 // if next or prev is_dir increment or decrement
 /*
   needs work.  nowNum starts at zero. DisplayNum is nowNum+1
   what about dirs? Robopages gets a default page which causes trouble.
   maybe the page numbers should be in the p2n file?
   .......or, listNode->idx is arbitrary.
   The dir and the next node can have the same idx if is_dir
 */

// if not mime type link
  $nextTargetType = $this->mimer->getRoboMimeType($nextUrl);
  $prevTargetType = $this->mimer->getRoboMimeType($prevUrl);
  if ($nextTargetType != 'link')
    $nextUrl = "?robopage=".$nextUrl;
   else
    $nextUrl = $_SESSION['currentClickDirUrl'] . basename($nextUrl);
  if ($prevTargetType != 'link')
    $prevUrl = "?robopage=".$prevUrl;
   else
    $prevUrl = $_SESSION['currentClickDirUrl'] . basename($prevUrl);
    
  //$displayNum = $nowNum + 1;
  $displayNum = $nowNum;
  //$ret .=  '<p class="pageNumber"> Page ' . $displayNum . '</p>';
  $ret .= '<div class="buttonbox">';

/*
  if(@stat($_SESSION['currentDirPath'] . 'roboresources/galleryMode/chapterImages'))
  {
    $ret .=  "\n". '<a class="button" href="?robopage='.$_GET['robopage'] . '&amp;layout=galleryMode' .'">Gallery mode</a><br/>'. "\n";
    $ret .=  '<a class="button" href="?robopage='.$_GET['robopage'] . '">Book mode</a><br/>'. "\n";
  }
*/

 
 //echo "nextTargetType: " , $nextTargetType, "<br/>"; 
 if($nextTargetType != 'link')
    $ret .=  '<a id="nextPageButton" class="button" onClick="clickNext()"  href="'.$nextUrl  .'">Next Page </a><br/>';
   else
    $ret .=  '<a target="_blank" id="nextPageButton" class="button" onClick="clickNext()"  href="'.$nextUrl  .'">Next Page </a><br/>';


 if($prevTargetType != 'link')
    $ret .=  '<a id="prevPageButton" class="button" onClick="clickPrev()"  href="'.$prevUrl  .'">Prev Page </a><br/>';
  else
    $ret .=  '<a target="_blank" id="prevPageButton" class="button" onClick="clickPrev()"  href="'.$prevUrl  .'">Prev Page </a><br/>';
  //$ret .=  '<a class="button" href="'.$prevUrl.'">Prev Page </a><br/>';
  //if(isset($_COOKIE['lastRobopage']))
  $bookTopDirComparitor = str_replace($_SESSION['prgrmDocRoot'], '',  $_SESSION['bookTop']);
  $lastPageFlag = isset($_COOKIE['lastRobopage']) ? 1 : 0;
  
/* 
  if( isset($_GET['robopage']) && $_GET['robopage'] == $bookTopDirComparitor )
  {
     $lastPageFlag=0;
  }
*/
  if($lastPageFlag)
  {
    $ret .=  "\n". '<a class="button" href="?robopage='.$_COOKIE['lastRobopage'] .'">Last Read</a><br/>'. "\n";
  }
  $ret .= '</div>';


  /*
  foreach (array_keys($this->p2nHandler->p2nHash) as $aPath)
  {
  $ret .= $aPath . " :: " . $this->p2nHandler->p2nHash[$aPath] . "<br/>";
  }
  */
  return($ret);
}
}
?>

