<?php

 @session_start();
 include_once("conf/globals.php");
 include_once("Link.php");
 include_once("dynamicNavigation.php");

 class galleryNavigation extends dynamicNavigation
 {

   function hasIndexImg($link, $mode = null)
   {
     $ret = '';
     $thumbName = 'tn-index.jpg';
     // tn-index.jpg needs to be generalized to is_image in index plus suffix
     $query = parse_url($link->href, PHP_URL_QUERY);
     parse_str($query, $parms);
     // we called this from linkTargetType=='dir' so we assume basename is a directory 
     if (isset($parms['robopage']))
     {
       // roboresources becomes resources with next Jul 4, '14 code update
       $base = basename($parms['robopage']);
       $thumbUrl = $_SESSION['currentClickDirUrl'] . $base . '/roboresources/thumbs/' . $thumbName;
       $testPath = $_SESSION['currentDirPath'] . $base . '/roboresources/thumbs/' . $thumbName;
       if ($mode != null && $mode == 'file')
       {
         $thumbName = 'tn-' . staticRoboUtils::stripSuffix(basename($parms['robopage'])) . '.jpg';
         $testPath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/' . $thumbName;
         $thumbUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/thumbs/' . $thumbName;
         //echo "new thumbName: ", $thumbName, '<br/>';
         //echo "testPath: ", $testPath, '<br/>';
       }
       //echo "testPath: ", $testPath, "<br/>";
       $test = @stat($testPath);
       if ($test != null)
       {
         //echo "testPath: ", $testPath, "<br/>";
         $ret = '<img src="' . $thumbUrl . '" alt="' . $base . '"/>';
         //echo htmlentities($ret), "<br/>";
       }
     }

     return $ret;
   }

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

       $indexImageTest = $this->hasIndexImg($link);

       if ($indexImageTest != null)
       {
         $linklbl = $linklbl . '<br/>' . $indexImageTest;
       } else
         $linklbl = '<img class="'.get_parent_class($this).' icon" src="' . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>' . $linklbl;
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
