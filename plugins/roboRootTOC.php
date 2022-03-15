<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("navCard.php");
include_once("dynamicNavigation.php");

class roboRootTOC  extends dynamicNavigation
{

   function init()
    {
        $this->linkshash = null;
        $this->linkshash = array();

        $this->fileKeys = null;
        $this->fileKeys = array();

        $this->imageKeys = null;
        $this->imageKeys = array();

        $this->dirKeys = null;
        $this->dirKeys = array();

        $this->mimer = new roboMimeTyper();

        $this->currentDirPath = $_SESSION['prgrmDocRoot'];
        $this->currentDirUrl = ''; 
        $this->currentClickDirUrl = 'fragments/';

        $this->gatherLinks();
    }

    function lookWhereForFiles()
    {
        $handle = @opendir($_SESSION['prgrmDocRoot']);
        return ($handle);
    }


    function getOutput($divid)
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $indexFlag = FALSE;
        $slideshowFlag = FALSE;
        $ret=$indexHref = '';

      $state = 'toc';
      if (isset($_COOKIE['buttonState']) 
          && in_array($_COOKIE['buttonState'], ['toc', 'TOC']))
      {
        if($_SESSION['layout'] != 'main'){
           $state = $_COOKIE['buttonState'];
           echo "roboRootTOC cookie main layout $state <br/>";
        } 
      }


 if($_SESSION['layout'] != 'mmmain'){
        $ret .= '<button id="tocPopper" onClick="flipAndRedraw()">'.$state.'</button>';
 } 
        echo "roboRootTOC now $state <br/>";
        $ret .= '<div id="tocComesAndGoes">';

        $cnt = count($this->linkshash);

        if (!$slideshowFlag && @stat($this->currentDirPath . 'roboresources/slideshow'))
        {
            $slideshowFlag = TRUE;
            if( $slideshowFlag )
              $ret .= $this->getSlideshowLink();
        }


        // fileKeys was made in the ctor
        $dcnt = count($this->dirKeys);
        $icnt = count($this->imageKeys);
        $fcnt = count($this->fileKeys);

/*
        for ($i = 0; $i < $dcnt; $i++)
        {
            $akey = $this->dirKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
            {
                $ret .= "\n" . $this->mkLink($link) . "\n";
            }
        }

        for ($i = 0; $i < $icnt; $i++)
        {
            $akey = $this->imageKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
            {
                $ret .= "\n" . $this->mkLink($link) . "\n";
            }
        }

        for ($i = 0; $i < $fcnt; $i++)
        {
            $akey = $this->fileKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
            {
                $ret .= "\n" . $this->mkLink($link) . "\n";
            }
        }

*/


$allOfEm = array_merge($this->dirKeys,$this->fileKeys,$this->imageKeys);
foreach($allOfEm as $aKey)
{
                //echo $aKey, "<br/>";
                $link = $this->linkshash[$aKey];
                $ret .= "\n" . $this->mkLink($link) . "\n";
} 


        // any index link like ?robopage=index.htm or index.jpg made to come last
        if ($indexFlag)
        {
            $ret .= "\n" . $this->mkLink($indexLink) . "\n";
        }
 
        $ret .= '</div>';

        $ret .= '<script> fixItUp("' . $state . '");</script>';
        return $ret;
    }
}
