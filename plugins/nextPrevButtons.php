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
public $urlCount;

function __construct()
{
  $this->init();
  $this->mimer = new RoboMimeTyper();
  $this->url2PageNodeHash = array();
  $this->pageNum2NodeHash = array();
  $this->orderedUrls = array();
  $this->pageLinkedList = new LinkedList();
  $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
  $this->p2nFileDir = trim(dirname($this->p2nFile) . '/');

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
  $pageNum = 0;
  $lines = file ($this->p2nFile);

  $lastDir=' -- ';
  foreach ($lines as $aline)
  {
    //echo $aline, "<br/>";
    $aline = trim($aline);
    $url = $this->bookRootSubPath . trim($aline);

    $testDirPath = $this->p2nFileDir . $aline;
    //if(!is_dir($testDirPath) || $testDirPath == $lastDir)
    if(!is_dir($testDirPath))
    {
       $testDirPath = dirname($testDirPath);
       if($lastDir == $testDirPath)
       {
          $lastDir = "--";
       }
       else{
          $pageNum += 1;
       }
       //echo "a $pageNum $url<br/>";
       $pageNode = new node($url,null,null,$pageNum);
    }
    else
    { 
          // is_dir
          if($lastDir != $testDirPath)
          {
             //echo "b $pageNum $url || $lastDir || $testDirPath<br/>";
             $pageNum += 1;
             $lastDir = $testDirPath;
          }
          else{
           //echo "c $pageNum $url || $lastDir || $testDirPath<br/>";
           $pageNum -= 1 ;
          }
          $url = $this->bookRootSubPath . trim($aline);
          $pageNode = new node($url,null,null,$pageNum);
             //else{ }
    }


    $this->pageLinkedList->ListAppend($pageNode);
    $this->url2PageNodeHash[$url] = $pageNode;
    $this->orderedUrls[] = $pageNode;
    $this->pageNum2NodeHash[$this->urlCount] = $pageNode;
    $this->urlCount++;
  }
}


function u2pDbg()
{
   //echo "urlCount: ", $this->urlCount, "<br/>";
   echo '<table style="font-size: 50%;">';
   
   //for($i=0; $i < $this->urlCount; $i++)
   for($i=0; $i < 10; $i++)
   {
     //$key = $this->orderedUrls[$i];
     //$node = $this->url2PageNodeHash[$key];
     $node = $this->orderedUrls[$i];
     $prev=$next=' -- ';
     if(isset($node->prev))
        $prev = $node->prev->dataObj;
     if(isset($node->next))
        $next = $node->next->dataObj;

     echo "<tr><td>",$prev, "</td><td>" , $node->idx, "</td><td><b>", $node->dataObj, "</b> </td><td>", $next, "</td></tr>";
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
  $nowNum = 1; // set a default?

 $robopage = '';
 $nowNode = NULL;
 if(isset($_GET['robopage']))
 {
    $robopage = $_GET['robopage'];
 }

 if($robopage == '')
 {
     $nowNum = 1;
     $nowNode = reset($this->pageNum2NodeHash);
 }
 else
 {
     //$nowNode = $this->pageNum2NodeHash[0];
     if(isset($this->orderedUrls[0])) 
        $nowNode = $this->orderedUrls[0];
    
     if(isset($this->url2PageNodeHash[$robopage]))
     {
       $nowNode = $this->url2PageNodeHash[$robopage];
       //echo "found ", $nowNode->idx, " " , $nowNode->dataObj, " on ", $robopage, "<br/>"; 
     }
     if(isset($nowNode) && $nowNode != NULL)
       $nowNum = $nowNode->idx;
     //$nowNode->nodeDbg();
 }

 // robopages accepts ?robopage=somdir as a url by finding a default display $_SESSION['currentDisplay']
 // if next buttons accept empty dir urls then next and prev will occasionally show same page twice
 if( (isset($nowNode) &&  $nowNode != NULl) && @is_dir($_SESSION['prgrmDocRoot'] . $nowNode->dataObj) && $nowNode->next != null)
    $nowNode = $nowNode->next;

  //$nowNode->nodeDbg();

  $nextNode = $prevNode = $nowNode;
  if(isset($nowNode))
     $nextUrl = $prevUrl = $nowNode->dataObj;

  if($nowNode && $nowNode->next != null)
  {
    $nextNode = $nowNode->next;
    $nextUrl = $nextNode->dataObj;
  } 
  else
  {
    $nextNode = $nowNode;
    $nextUrl =  '';
    if(isset($nowNode) && isset($nowNode->dataObj))
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
    $prevUrl = '';
    if(isset($nowNode) && isset($nowNode->dataObj))
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
  $ret .=  '<b class="pageNumber"> Page ' . $displayNum . '</b>';
  $ret .= '<p class="buttonbox">';

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

