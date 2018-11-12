<?php

 @session_start();
 include_once("conf/globals.php");
 include_once("Link.php");
 include_once("dynamicNavigation.php");

 class galleryNavigation extends dynamicNavigation
 {

   function mkLink($link, $linkTargetType)
   {
     global $sys_thumb_links;
     $ret = "\n\n" . '<div class="'.get_class($this).'">';
     $ret .= "\n" . '<a href="' . $link->href . '">' . "\n";

     /* 
     ** mkLink receives a Link object $link
     ** mkLink munges the default $link->label if else if else else until the last line 
     ** where the hyperlink is assembled and then returned (after munging the label)
     */

     // get a default linklbl
     $linklbl = staticRoboUtils::mkLabel($link->label);


     if ($linkTargetType == 'dir')
     {
         //$linklbl = '<img class="'.get_class($this) .' icon" src="' . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>'. "&nbsp;"  . $linklbl ;
     }

     // grep -iH actionItem *php which of the follwing ifs?
     //else if ($linkTargetType == 'image' && $sys_thumb_links && strstr($link->href, 'robopage=')) 
     else if ($linkTargetType == 'image' && $sys_thumb_links)
     {
       //$linkTargetType = ''; ??????????????????
       $query = parse_url($link->href, PHP_URL_QUERY);
       parse_str($query, $parms);
       if (isset($parms['robopage']))
       {
         $base = basename($parms['robopage']);
         $tpath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/tn-' . $base;

         if (@stat($tpath)) 
         {
           //$thumb = '<img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . $linklbl . '"/>';
           $thumb = '<img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . 'xxx' . '"/>';
           //echo htmlentities($linklbl), "<br/>";
           $linklbl = "\n".$this->thumbMemer($thumb,$linklbl)."\n";    
        }
       }
     }


    // $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";
     $ret .=   $linklbl . '</a>' . "\n";
     $ret .= '</div>';
     //echo htmlentities($ret), "<br/><br/>\n\n";
     return $ret;
   }



 }

?>
