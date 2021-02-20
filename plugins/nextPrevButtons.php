<?php
@session_start();
include_once("plugin.php");
include_once("LinkedList.php");
include_once("roboMimeTyper.php");

class nextPrevButtons extends plugin
{
protected $p2nFile;
protected $p2nFileDir;
protected $bookRootSubPath;
protected $url2PageNodeHash;
protected $pageNum2NodeHash;
protected $orderedUrls;
protected $pageLinkedList;
protected $mimer;
protected $getRobopage;
public $urlCount;

function __construct()
{
  $this->init();
  $this->mimer = new RoboMimeTyper();
  $this->url2PageNodeHash = array();
  $this->pageNum2NodeHash = array();
  $this->orderedUrls = array();
  //$this->pageLinkedList = new LinkedList();
  $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
  $this->p2nFileDir = dirname($this->p2nFile) . '/';

  $this->bookRootSubPath = str_replace($_SESSION['prgrmDocRoot'],'',$this->p2nFileDir);
  $this->urlCount=0;
}

function findP2NFile($dir)
{
  if(!is_dir($dir))
  {
      $dir = dirname($dir) . '/';
  }
  if(@stat($dir . 'p2n'))
  {
    return($dir . 'p2n');
  }
  else
    return($this->findP2NFile(dirname($dir) . '/'));
}


protected function readP2NFile()
{
  $this->urlCount=0;
  $this->pageLinkedList = null;
  $this->pageLinkedList = new LinkedList();
  $lines = file ($this->p2nFile);
  foreach ($lines as $aline)
  {
    $url = $this->bookRootSubPath . trim($aline);
    $pageNode = new node($url,null,null,$this->urlCount);
    //$pageNode->nodeDbg();
    $this->pageLinkedList->ListAppend($pageNode);
    $this->url2PageNodeHash[$url] = $pageNode;
    $this->orderedUrls[] = $url;
    $this->pageNum2NodeHash[$this->urlCount] = $pageNode;
    $this->urlCount++;
  }
}


function u2pDbg()
{
   echo '<table style="font-size: 50%;">';
   for($i=0; $i < $this->urlCount; $i++)
   {
     $key = $this->orderedUrls[$i];
     $node = $this->url2PageNodeHash[$key];
     $prev=$next=' -- ';
     if(isset($node->prev))
        $prev = $node->prev->dataObj;
     if(isset($node->next))
        $next = $node->next->dataObj;

     echo "<tr><td>",$prev, "</td><td>" , $node->idx + 1, "</td><td><b>", $node->dataObj, "</b> </td><td>", $next, "</td></tr>";
   }
   echo "</table>";
}

function getNextCookieJS()
{
 $ret = <<<ENDO
<script>
function clickNext()
{ 
 var value = document.getElementById("nextPageButton").getAttribute("href").replace("?robopage=",'');
 var cookie="lastRobopage=" + value ;
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
 var cookie="lastRobopage=" + value ;
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
  $this->readP2NFile();
  //$this->u2pDbg();
  $nowNum = -1;

 $this->getRobopage = '';
 if(isset($_GET['robopage']) && $_GET['robopage'] != null)
 {
    $this->getRobopage = StaticRoboUtils::fixPageEqualParm($_GET['robopage']);
 }

 if($this->getRobopage == '')
 {
     $nowNum = 0;
     $nowNode = reset($this->pageNum2NodeHash);
 }
 else
 {
     //$nowNode = $this->pageNum2NodeHash[0];
   
     if(isset($this->url2PageNodeHash[$this->getRobopage]))
     {
       $nowNode = $this->url2PageNodeHash[$this->getRobopage];
     }
     $nowNum = $nowNode->idx;
 }

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
    
  $displayNum = $nowNum + 1;
  $ret .=  '<b class="pageNumber"> Page ' . $displayNum . '</b>';
  $ret .= '<p class="buttonbox">';

 //echo "nextTargetType: " , $nextTargetType, "<br/>"; 
 if($nextTargetType != 'link')
    $ret .=  '<a id="nextPageButton" class="button" onClick="clickNext()"  href="'.$nextUrl  .'">Next Page </a><br/>';
   else
    $ret .=  '<a target="_blank" id="nextPageButton" class="button" onClick="clickNext()"  href="'.$nextUrl  .'">Next Page </a><br/>';


 if($prevTargetType != 'link')
    $ret .=  '<a id="prevPageButton" class="button" onClick="clickPrev()"  href="'.$prevUrl  .'">Prev Page </a><br/>';
  else
    $ret .=  '<a target="_blank" id="prevPageButton" class="button" onClick="clickPrev()"  href="'.$prevUrl  .'">Prev Page </a><br/>';

  $bookTopDirComparitor = str_replace($_SESSION['prgrmDocRoot'], '',  $_SESSION['bookTop']);
  $lastPageFlag = isset($_COOKIE['lastRobopage']) ? 1 : 0;
   
  if( isset($this->getRobopage) && $this->getRobopage == $bookTopDirComparitor )
  {
     $lastPageFlag=0;
  }
  if($lastPageFlag)
  {
    $ret .=  "\n". '<a class="button" href="?robopage='.$_COOKIE['lastRobopage'] .'">Last Read</a><br/>'. "\n";
  }
  $ret .= '</p>';

 
  return($ret);
}
}
?>

