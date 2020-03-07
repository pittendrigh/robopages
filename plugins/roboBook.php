<?php

@session_start();

include_once("plugin.php");

class roboBook extends plugin 
{
    protected $p2nFile;
    protected $p2nHash;
    protected $n2pArray;
    protected $maxPageNum = 0;

    public function __construct()
    {
        $this->init();
        if(@stat($this->p2nFile = $_SESSION['currentDirPath'] . 'p2n'))
        {
                $this->p2nFile = $_SESSION['currentDirPath'] . 'p2n';
                $_SESSION['p2nFile'] = $this->p2nFile;
        }
        else if (isset($_SESSION['p2nFile']))
                $this->p2nFile = $_SESSION['p2nFile'];
    }


    protected function setP2NFile()
    {
       // this will break if the roboBook layout applied outside path/path/fragments/Library/someDir/
       // Library from a conf/globals.php?  grep -iH actionItem *php
       $path = str_replace('Library/','', $_SESSION['currentDirUrl']);
       $pathBits = explode('/',$path);
       $this->p2nFile = $_SESSION['prgrmDocRoot'] . 'Library/' . trim($pathBits[0] . '/p2n' );
       echo "p2nFile: ", $this->p2nFile, "<br/>"; 
    }

    protected function readP2NFile()
    {
      /**
      **  The p2n location needs a location convention.
      **  We need a solution that works for a starting point.
      **  A robopages site may have no books. 
      **
      **  If there are one or more books they must go into /fragments/Books
      **  /fragments/Books/BookOne, BookTwo etc.  That might go in conf/dirLayouts.ini
      **  So if any codes below fail to stat a p2n file, perhaps in a nested directory, then  
      **  the first directory chuck below /fragments/Books is this book, 
      **  and is also where the relevant p2n resides 
      **/

      // TOC?  dynamicNavigation for now.  Better is needed
      // A robopages book might have arbitrary contents directory nesting,
      // but in the TOC only the first level chapaters will snow, 
      // dynamicNavigation-like directory contents links for the current
      // chapter only

      $this->setP2NFile();
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
             echo "setting currentPageNum: " . $_SESSION['currentPageNum'] . "<br/>";
         }
      }
    }
    
    public function getOutput($divid)
    {
        $this->readP2NFile();
//foreach (array_keys($this->n2pArray) as $apnKey) { $url = $this->n2pArray[$apnKey]; echo $apnKey, " ", $url, "<br/>"; }

        $ret = '';
/*
       if(isset($_SESSION['currentPageNum']))
        $ret .= "currentPageNum: " . $_SESSION['currentPageNum'] . "<br/>";
       else
       {
       $_SESSION['currentPageNum'] = 0;
        $ret .= "no currentPageNum for some reason<br/>";
       }
*/

//       echo "robopage: ", $_GET['robopage']. "<br/>";
//       echo "current Page??? ", $this->p2nHash[$_GET['robopage']]. "<br/>";
        $nowNum = $_SESSION['currentPageNum'];
        $nextNum = $nowNum + 1;
        $prevNum = $nowNum - 1;
        if($nextNum > $this->maxPageNum)
            $nextNum = $this->maxPageNum;
        if($prevNum < 0)
            $prevNum = 0;
           
        $ret .= "maxPn: " . $this->maxPageNum . " "; 
        $ret .= "nextPn: " . $nextNum . " "; 
        $ret .= "prevPn: " . $prevNum . "<br/> "; 

$nextr = $this->n2pArray[intval($nextNum)];
$prevr = $this->n2pArray[intval($prevNum)];
$nextUrl = $_SERVER['PHP_SELF'] . "?robopage=" . $nextr; 
$prevUrl = $_SERVER['PHP_SELF'] . "?robopage=" . $prevr; 

$ret .=  '<form method="get" action="'.$nextUrl.'">
            <input type="hidden" name="robopage" value="'.$nextUrl.'"/> '.$nextUrl.'
            <input type="submit" value="Next"/>
         </form> ';

$ret .=  '<form method="get" action="'.$prevUrl.'">
             <input type="hidden" name="robopage" value="'.$prevUrl.'"/> '.$prevUrl.'
             <input type="submit" value="Prev"/>
          </form> ';
          $ret .= "<br/><br/>";
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
