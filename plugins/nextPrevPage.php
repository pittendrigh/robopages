<?php

@session_start();

include_once("plugin.php");

class nextPrevPage extends plugin 
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
       // this will break if the nextPrevPage layout applied outside path/path/fragments/Library/someDir/
       // Library from a conf/globals.php?  grep -iH actionItem *php
       $path = str_replace('Library/','', $_SESSION['currentDirUrl']);
       $pathBits = explode('/',$path);
       $this->p2nFile = $_SESSION['prgrmDocRoot'] . 'Library/' . trim($pathBits[0] . '/p2n' );
       //echo "p2nFile: ", $this->p2nFile, "<br/>"; 
    }

    protected function readP2NFile()
    {
      /**
      **  The p2n location needs a location convention.
      **  For any path underneath /fragments/Libray will be set in conf/dirlayouts.ini
      */

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
             //echo "setting currentPageNum: " . $_SESSION['currentPageNum'] . "<br/>";
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

       //echo "robopage: ", $_GET['robopage']. "<br/>";
       //echo "current Page??? ", $this->p2nHash[$_GET['robopage']]. "<br/>";
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

//echo "nextr: ", $nextr, "<br/>";
//echo "prevr: ", $prevr, "<br/>";

$nextUrl = "?robopage=" . $nextr; 
$prevUrl = "?robopage=" . $prevr; 
$nowNum++;

//$ret .= '<p class="buttonbox"> <b class="flexed">Page '.$nowNum.'</b> &nbsp; <a class="button" href="'.$nextUrl.'">Next</a>  <a class="button" href="'.$prevUrl.'">Prev</a> </p>';
$ret .=  '<h4 style="margin: 0; padding: 0; border: 0; text-align: center">Page '.$nowNum.'</h4>';
$ret .= '<p class="buttonbox">';
$ret .=  '<a class="button" href="'.$nextUrl.'">Next page</a><br/>';
$ret .=  '<a class="button" href="'.$prevUrl.'">Prev page</a><br/>';
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
