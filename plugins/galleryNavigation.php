<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("navCard.php");
include_once("dynamicNavigation.php");

class galleryNavigation extends dynamicNavigation
{

/*
    function lookWhereForFiles()
    {
        // oops no opendir error handling grep -i actionItem *php
        $handle = @opendir($this->currentDirPath . 'roboresources/pics');
        return ($handle);
    }
*/


    function findFirstJpg($dir)
    {
       $ret = '';
       $dh = @opendir($dir);
      
       //echo "findFirstJpg dir: " . $dir . "<br/>"; 
       while ($dh && $file = readdir($dh)) 
       {
           if ($file != "." && $file != "..") 
           {
               if(preg_match('/jpg/', $file)) 
               {
                   //echo "findFirstJpg: " . $file . "<br/>"; 
                   
                   $ret = $file;
                   break;
               }
           }
       }
       return ($ret);
    }

    function hasIndexImg($link, $mode = null)
    {
        $ret = '';
        $thumbName = 'tn-index.jpg';

        $query = parse_url($link->href, PHP_URL_QUERY);
        parse_str($query, $parms);

        // we called this from linkTargetType=='dir' so we assume basename is a directory 
        if (isset($parms['robopage']))
        {
            $base = basename($parms['robopage']);
            $thumbDirUrl = $_SESSION['currentClickDirUrl'] . $base . '/roboresources/thumbs/';
            $thumbUrl = $thumbDirUrl . $thumbName;
            $testDir = $_SESSION['currentDirPath'] . $base . '/roboresources/thumbs/';
            $testPath = $testDir . $thumbName;
/*          ......assuming directory behavior for now. 
            if ($mode != null && $mode == 'file')
            {
                $thumbName = 'tn-' . StaticRoboUtils::stripSuffix(basename($parms['robopage'])) . '.jpg';
                $testPath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/' . $thumbName;
                $thumbUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/thumbs/' . $thumbName;
            }
*/
            if(file_exists($testPath))
            {
                $ret = $thumbUrl ;
            }
            else{
              $thumb = $this->findFirstJpg($testDir);
              $ret  = $thumbDirUrl . $thumb;
            }
        }

        return $ret;
    }

    function getSlideshowLink()
   {
          $slideshowLink = '?robopage=' . $_GET['robopage'] . '&amp;layout=slideshow';
          $card = new navcard($slideshowLink, "Slideshow","","slideshow" );
          $ret = $card->getOutput('');
          return ($ret);
    }


    /* link is to an 1 external page, 2 to an internal page or 3 to an internal directory 
           The link object has a label that is (usually) what is expected 
           link objects have only one constructor and are made from a file line
           ?robopage=Flies/Fred-Nelson::Fred-Nelson::dir

           body is link label
           body is link label unless thumb available
           body is link label unless thumb available
    */
    function mkLink($link, $LinkTargetType=null)
    {
        global $sys_thumb_links;
        $ret = $testPath = $body = '';

        /* the navcard as a whole will be the incoming href
           if thumb available body is thumb, else empty 
           label is incomding label unless $linkTargetType is "dir"
           if dir label is prepended with folder font symbol 
        */
        $href=$link->href;
        $label = StaticRoboUtils::mkLabel($link->label);
        $thumbPath='';

        $linkTargetType = $link->linkTargetType;

        if ($linkTargetType == 'image' && $sys_thumb_links)
        {
            $query = parse_url($link->href, PHP_URL_QUERY);
            parse_str($query, $parms);
            if (isset($parms['robopage']))
            {
                $base = basename($parms['robopage']);
                $tpath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/tn-' . $base;

                if (@stat($tpath))
                {
                    $thumbPath = $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base;
                }
            }
        }

        /* at this point thumbPath may or may not be empty 
           label (should) be set by incoming $link object 
           only the body now needs adjustment
        */

        // thumbPath is not null iff there is an appropriate thumbnail
        if(isset($thumbPath) && $thumbPath != null)
        {
          $body = '<img src="' . $thumbPath . '" alt="' . $label . '" loading="lazy"/>';
        }

if ($linkTargetType  == 'dir')
{
        $body='';
        $partPath = $this->hasIndexImg($link);
        $testPath = $_SERVER['DOCUMENT_ROOT'] . $partPath; 
        //if(@stat($testPath))
        if($partPath != null)
        {
           $body = '<img src="' . $partPath . '" alt="'. $label. '" loading="lazy"/>';
        }
        $label = '<i class="material-icons" style="font-size: 80%; ">folder</i> '  . $label;
        $card = new navcard($href,$body, $label);
}
else 
{
        $card = new navcard($href,$body, $label);
}
        $ret .= $card->getOutput('');

        return $ret;
    }

}
?>
