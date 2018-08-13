<?php

 @session_start();
 include_once("conf/globals.php");
 include_once("Link.php");
 include_once("plugin.php");
 include_once("dynamicNavigation.php");

 class galleryNavigation extends dynamicNavigation
 {

   function __construct()
   {
     $this->slideshowFlag = TRUE;
     $this->linkshash = array();
     $this->dirKeys = array();
     $this->fileKeys = array();
     $this->imageKeys = array();
     $this->mimer = new roboMimeTyper();
     $this->init();
     $this->gatherLinks();
   }

   function getOutput($divid)
   {
     global $sys_show_suffixes, $sys_thumb_links;

     $indexFlag = FALSE;
     $slideshowFlag = FALSE;
     $indexHref = '';


     $ret = $class = '';
     $lbl = '';

     $cnt = count($this->linkshash);

     if (!$slideshowFlag && @stat($_SESSION['currentDirPath'] . 'roboresources/slideshow'))
     {
       $slideshowFlag = TRUE;
       $ret .= '<p><a class="slideshow" href="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=lazyloadslideshow">Slideshow</a></p>';
     }

     $dcnt = count($this->dirKeys);
     $icnt = count($this->imageKeys);
     $fcnt = count($this->fileKeys);

     for ($i = 0; $i < $dcnt; $i++)
     {
       $akey = $this->dirKeys[$i];
       $link = $this->linkshash[$akey];
       if ($link != null && !stristr($link->href, "slideshow"))
       {
         $ret .= '<p class="robonav">' . $this->mkLink($link, "dir") . '</p>';
       }
     }


     for ($i = 0; $i < $fcnt; $i++)
     {
       $akey = $this->fileKeys[$i];
       if ($akey == 'index.htm')
       {
         $indexFlag = TRUE;
         $indexLink = $this->linkshash['index.htm'];
         continue;
       }
       $link = $this->linkshash[$akey];
       if ($link != null && !stristr($link->href, "slideshow"))
         $ret .= '<p class="robonav">' . $this->mkLink($link, "file") . '</p>';
     }

     for ($i = 0; $i < $icnt; $i++)
     {
       $akey = $this->imageKeys[$i];
       $link = $this->linkshash[$akey];
       $ret .= '<p class="robonav">' . $this->mkLink($link, 'image') . '</p>';
     }

     if ($indexFlag)
     {
       $ret .= '<p class="robonav">' . $this->mkLink($indexLink, "file") . '</p>';
     }

     // if ($cnt > 0)
     //    $ret .= '</ul>';
     return $ret;
   }

 }

?>
