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

    function getOutput($divid)
    {
      $ret = '<div class="buttonbox">';

      // what is the incoming landscape?
      // $_SESSION['currentDirUrl']
      // $_SESSION['currentDisplay']
      // $_GET['robopage']
      // $_COOKIE['lastRobopage']
      // 
      // Synching roboBook's lastRobopage with currentDispaly
      // would require refactoring higher up the oop food chain
      //
      // For now any not leaf level url ?robopage=SomeRoboBook might open to
      // $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']
      // without knowledge of $_COOKIE['lastRobopage']
      //
      // If properly synched the user can now click the "Last Read" button
      //
      // That leaves the possibility of synching 
      // lastRobopage with initial button states
      // For now punt.  The display opens to what ever it opens to.
      // The user can still click the "Last Read" button and go there.
      //


      $robopage = '' ;
      $nowNode = $this->p2nHandler->pageLinkedList->getHead();
      $nowUrl = $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay'];
      if(isset($this->p2nHandler->url2PageNodeHash[$nowUrl]))
        $nowNode = $this->p2nHandler->url2PageNodeHash[$nowUrl];

      if(isset($_GET['robopage']) && $_GET['robopage'] != '') 
        $robopage = $_GET['robopage'];
 
      if($robopage != '') 
      {
        if (isset($this->p2nHandler->url2PageNodeHash[$robopage]))
        {
          $nowNode = $this->p2nHandler->url2PageNodeHash[$robopage];
        }
      }

      // the following is a hack to fix not leaf level urls to book chapters 
      // like the p2n convention of:
      // somedirectory
      // somedirectory/index.htm
      //
      if (@is_dir($_SESSION['prgrmDocRoot'] . $nowNode->dataObj) && $nowNode->next != null)
        $nowNode = $nowNode->next;

      // set some more defaults
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

      $ret .= '<div class="buttonbox">';

      if ($nextTargetType != 'link')
        $ret .= '<a class="button"   
             href="' . $nextUrl . '">Next Page </a><br/>';
      else
        $ret .= '<a target="_blank" class="button" 
              href="' . $nextUrl . '">Next Page </a><br/>';

      if ($prevTargetType != 'link')
        $ret .= '<a class="button" 
               href="' . $prevUrl . '">Prev Page </a><br/>';
      else
        $ret .= '<a target="_blank" class="button" 
               href="' . $prevUrl . '">Prev Page </a><br/>';

      $bookTopDirComparitor = str_replace($_SESSION['prgrmDocRoot'], '', $_SESSION['bookTop']);
      $lastPageFlag = isset($_COOKIE['lastRobopage']) ? 1 : 0;

      if ($lastPageFlag)
      {
        $ret .= "\n" . '<a class="button" href="?robopage='
                . $_COOKIE['lastRobopage'] . '">Last Read</a><br/>' . "\n";
      }
      $ret .= '</div>';

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

