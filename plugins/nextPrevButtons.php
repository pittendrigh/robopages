<?php

  @session_start();
  include_once("plugin.php");
  include_once("roboMimeTyper.php");
  include_once("p2nHandler.php");

  class nextPrevButtons extends plugin
  {
    protected $mimer;
    protected $p2nHandler;

    function __construct()
    {
      $this->mimer = new RoboMimeTyper();
      $this->p2nHandler = new p2nHandler();
    }

    function getNextCookieJS(){
      $ret = <<<ENDO
<script>
function clickNext(){ 
 var buttons = document.getElementsByClassName("nextPageButton");
 var firstButton = buttons[0];
 var value = firstButton.getAttribute("href").replace("?robopage=",'');
 var date = new Date();
 date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000)); 
 var expiresStr = "expires=" + date.toUTCString();
 var str = 'lastRobopage=' + value + "; " + expiresStr + "; SameSite=Lax;"; 
 document.cookie = str;
}
</script>
ENDO;

      return($ret);
    }  

    function getPrevCookieJS(){
      $ret = <<<ENDO
<script>
function clickPrev(){ 
 var buttons = document.getElementsByClassName("prevPageButton");
 var firstButton = buttons[0];
 var value = firstButton.getAttribute("href").replace("?robopage=",'');
 var date = new Date();
 date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
 var expiresStr = "expires=" + date.toUTCString();
 var str = 'lastRobopage=' + value + "; " + expiresStr + "; SameSite=Lax;";
 document.cookie = str;
}
</script>
ENDO;

      return($ret);
    }  
//document.cookie = 'lastRobopage' + "=" + value + "; " + expires + "; path=/; SameSite=Lax;";

    function getOutput($divid)
    {
      $ret = '';
      $ret .= $this->getNextCookieJS();
      $ret .= $this->getPrevCookieJS();

      // the following needs to be a plugin and not hard coded? 
      $ret .= '<div class="buttonbox">';

      // what is the incoming landscape?
      // $_SESSION['currentDirUrl']
      // $_SESSION['currentDisplay']
      // $_GET['robopage']
      // $_COOKIE['lastRobopage']
      // 
      // Synching roboBook's lastRobopage with currentDispaly
      // would require refactoring higher up the oop food chain
      //
      $robopage = '' ;

      // make a fall back default for nowNode
      $nowNode = $lastGoodNode = $this->p2nHandler->pageLinkedList->getHead();

      if(isset($_GET['robopage']) && $_GET['robopage'] != '') 
        $robopage = $_GET['robopage'];

      if(isset($robopage) && $robopage != '') {
        if (isset($this->p2nHandler->url2PageNodeHash[$robopage]))
        {
          $nowNode = $this->p2nHandler->url2PageNodeHash[$robopage];
          $nowUrl = $robopage;
          $_SESSION['lastP2nUrl'] = $nowUrl;
        }
        else
        {
        // else maybe we descended into a gallery and clicked a
        // gallery link, and are hence out of the p2n system  
        if(isset($_SESSION['lastP2nUrl'])){
           $lastP2nUrl = $_SESSION['lastP2nUrl'];
           if(isset($this->p2nHandler->url2PageNodeHash[$lastP2nUrl]))
           {
              $lastGoodNode 
                 = $this->p2nHandler->url2PageNodeHash[$lastP2nUrl];
           $nowUrl = $lastGoodNode->dataObj;
           $nowNode = $this->p2nHandler->url2PageNodeHash[$nowUrl];
           }
         }
      }
   }

      ///////////// should have the right nowNode by now
      //
      // the following is a hack to fix not leaf level urls to book chapters 
      // like the p2n convention of:
      // somedirectory
      // somedirectory/index.htm
      //
      if (@is_dir($_SESSION['prgrmDocRoot'] . $nowNode->dataObj) && $nowNode->next != null)
        $nowNode = $nowNode->next;

      $nextNode = $prevNode = $nowNode;
      $nextUrl = $prevUrl = $nowNode->dataObj;

      // note there these nodes are all part of a LinkedList
      // but for now we do not use the list. Only the nodes,
      // which we get from p2nHandler->url2PageNodeHash[]
      //
      if ($nowNode->next != null)
      {
        $nextNode = $nowNode->next;
        $nextUrl = $nextNode->dataObj;
      }
      else
      {
        $nextNode = $nowNode;
        $nextUrl = $nowNode->dataObj;
      }

      if (isset($nowNode->prev) && $nowNode->prev != null)
      {
        $prevNode = $nowNode->prev;
        if (@is_dir($_SESSION['prgrmDocRoot'] . $prevNode->dataObj))
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


      // note two the buttoms we make are HTML hyperlinks styled like
      // a button
      //
      if ($nextTargetType != 'link')
        $nextUrl = "?robopage=" . $nextUrl;
      else
        $nextUrl = $_SESSION['currentClickDirUrl'] . basename($nextUrl);

      if ($prevTargetType != 'link')
        $prevUrl = "?robopage=" . $prevUrl;
      else
        $prevUrl = $_SESSION['currentClickDirUrl'] . basename($prevUrl);

      if ($nextTargetType != 'link')
        $ret .= '<a  class="nextPageButton" onClick="clickNext()"  
             href="' . $nextUrl . '">Next Page </a>';
      else
        $ret .= '<a target="_blank"  class="nextPageButton"  
             onClick="clickNext()"  href="' . $nextUrl . '">Next Page </a>';

      if ($prevTargetType != 'link')
        $ret .= '<a  class="prevPageButton" 
              onClick="clickPrev();"  href="' . $prevUrl . '">Prev Page </a>';
      else
        $ret .= '<a target="_blank"  class="prevPageButton"   
              onClick="clickPrev();"  href="' . $prevUrl . '">Prev Page </a>';

      $bookTopDirComparitor = str_replace($_SESSION['prgrmDocRoot'], '', $_SESSION['bookTop']);
      $lastPageFlag = isset($_COOKIE['lastRobopage']) ? 1 : 0;


      if ($lastPageFlag)
      {
        $ret .= "\n" . '<a  class="lastReadPageButton" href="?robopage='
                . $_COOKIE['lastRobopage'] . '">Last Read</a>' . "\n";
      }
      //$ret .= '</div>';

      /*
        foreach (array_keys($this->p2nHandler->p2nHash) as $aPath)
        {
        $ret .= $aPath . " :: " . $this->p2nHandler->p2nHash[$aPath] . "<br/>";
        }
       */

      $ret .= '</div>';
      return($ret);
    }

  }
?>

