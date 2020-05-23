<?php
@session_start();
include_once("plugin.php");
include_once("LinkedList.php");

class nextPrevButtons extends plugin
{
protected $p2nFile;
protected $url2PageNodeHash;
protected $pageNum2NodeHash;
protected $orderedUrls;
protected $pageLinkedList;
public $urlCount;

public function __construct()
{
  $this->init();
  $this->url2PageNodeHash = array();
  $this->pageNum2NodeHash = array();
  $this->orderedUrls = array();
  $this->pageLinkedList = new LinkedList();
  $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
  $this->urlCount=0;
}

/*
public function getP2NFile()
{
  return $this->p2nFile;
}
*/

public function findP2NFile($dir)
{
  //echo " &nbsp; nextPrev findP2NFile: ", $dir, "<br/>";
  if(!is_dir($dir))
  {
      $dir = dirname($dir) . '/';
  }
  if(@stat($dir . 'p2n'))
  {
    //echo "returning ", $dir, "p2n<br/>";
    return($dir . 'p2n');
  }
  else
    return($this->findP2NFile(dirname($dir) . '/'));
}


protected function readP2NFile()
{
  //echo "nextPrevButtons->readP2NFile: ", $this->p2nFile, "<br/>";
  $lines = file ($this->p2nFile);
  foreach ($lines as $aline)
  {
    $url = trim($aline);
    $pageNode = new node($url,null,null,$this->urlCount);
    //echo $aline, "<br/>";
    //$pageNode->nodeDbg();
    $this->pageLinkedList->ListAppend($pageNode);
    $this->url2PageNodeHash[$url] = $pageNode;
    $this->orderedUrls[] = $url;
    $this->pageNum2NodeHash[$this->urlCount] = $pageNode;
    $this->urlCount++;
  }
}

public function u2pDbg()
{
   //echo "imax: ", $this->urlCount, "<br/>";
   for($i=0; $i < $this->urlCount; $i++)
   {
     $key = $this->orderedUrls[$i];
     $node = $this->url2PageNodeHash[$key];
     $prev=$next=' -- ';
     if(isset($node->prev))
        $prev = $node->prev->dataObj;
     if(isset($node->next))
        $next = $node->next->dataObj;

     //echo $node->idx, " " , $node->dataObj, "<br/>";
     //echo "<tr><td>",$prev, "</td><td>" , $node->idx + 1, "</td><td><b>", $node->dataObj, "</b> </td><td>", $next, "</td></tr>";
   }
   //echo "</table>";
}

public function getOutput($divid)
{
  $ret = '';

  //echo "found p2nfile: ", $this->p2nFile, "<br/>";
  $this->readP2NFile();
  //$this->u2pDbg();
  $nowNum = 0;

 $robopage = '';
 if(isset($_GET['robopage']))
 {
    $robopage = $_GET['robopage'];
    //echo "robopage " ,$robopage, "<br/>";
 }

 if($robopage == '')
 {
     $nowNum = 0;
     $nowNode = $this->pageNum2NodeHash[0];
     //echo "defaulted nowNum as 0 <br/>";
 }
 else
 {
     $nowNode = $this->pageNum2NodeHash[0];
     
     //$robopage = str_replace("//","/",$robopage);
     if(isset($this->url2PageNodeHash[$robopage]))
       $nowNode = $this->url2PageNodeHash[$robopage];
     $nowNum = $nowNode->idx;
     //echo "just set nowNum as: ", $nowNum, "<br/>";
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

  //if($nowNode == null) echo "null nowNode<br/>";
  if(isset($nowNode->prev) && $nowNode->prev != null)
  {
    $prevNode = $nowNode->prev;
    $prevUrl = $prevNode->dataObj;
  } 
  else
  {
    $prevNode = $nowNode;
    $prevUrl = $nowNode->dataObj;
  } 

  $nextUrl = "?robopage=".$nextUrl;
  $prevUrl = "?robopage=".$prevUrl;
  $displayNum = $nowNum + 1;
  $ret .=  '<b class="pageNumber"> Page ' . $displayNum . '</b>';
  $ret .= '<p class="buttonbox">';


  if(@stat($_SESSION['currentDirPath'] . 'roboresources/galleryMode/chapterImages'))
  {
    $ret .=  "\n". '<a class="button" href="?robopage='.$_GET['robopage'] . '&amp;layout=galleryMode' .'">Gallery mode</a><br/>'. "\n";
    $ret .=  '<a class="button" href="?robopage='.$_GET['robopage'] . '">Book mode</a><br/>'. "\n";
  }

  $ret .=  '<a class="button" href="'.$nextUrl  .'">Next Page </a><br/>';
  $ret .=  '<a class="button" href="'.$prevUrl.'">Prev Page </a><br/>';
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

