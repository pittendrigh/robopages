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
            $base = basename($parms['robopage']);
            $thumbUrl = $_SESSION['currentClickDirUrl'] . $base . '/roboresources/thumbs/' . $thumbName;
            $testPath = $_SESSION['currentDirPath'] . $base . '/roboresources/thumbs/' . $thumbName;
            if ($mode != null && $mode == 'file')
            {
                $thumbName = 'tn-' . StaticRoboUtils::stripSuffix(basename($parms['robopage'])) . '.jpg';
                $testPath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/' . $thumbName;
                $thumbUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/thumbs/' . $thumbName;
            }
            //echo "testPath: ", $testPath, "<br/>";
            $test = @stat($testPath);
            if ($test != null)
            {
                //$ret = '<img src="' . $thumbUrl . '" alt="' . $base . '"/>';
                $ret = $thumbUrl ;
            }
        }

        return $ret;
    }

    function getSlideshowLink()
   {
          $slideshowLink = '?robopage=' . $_GET['robopage'] . '&amp;layout=slideshow';
          $card = new navCard($slideshowLink, "Slideshow","","slideshow" );
          $ret = $card->getOutput('');
          return ($ret);
    }


    function mkLink($link, $LinkTargetType=null)
    {
        global $sys_thumb_links;
        $ret = '';

        $href=$link->href;
        $label = StaticRoboUtils::mkLabel($link->label);
        //$label = $link->label;
        $imgPath='';

        $linkTargetType = $link->linkTargetType;
        //if($linkTargetType == 'fragment') echo "label: ", $label, "<br/>";

        if ($linkTargetType == 'dir')
        {
            $imgPath = $this->hasIndexImg($link);
        }
        else if ($linkTargetType == 'image' && $sys_thumb_links)
        {
            $query = parse_url($link->href, PHP_URL_QUERY);
            parse_str($query, $parms);
            if (isset($parms['robopage']))
            {
                $base = basename($parms['robopage']);
                $tpath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/tn-' . $base;

                if (@stat($tpath))
                {
                    $imgPath = $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base;
                }
            }
            $label = StaticRoboUtils::mkLabel(basename($imgPath));
            $body = '<img class="navCardImg" src="' . $imgPath . '" alt="' . $label . '"/>';
        }


if ($linkTargetType  == 'dir')
{
        $label = '<i class="material-icons" style="font-size: 80%; ">folder</i> '  . basename($href);
        //if(isset($imgPath) && $imgPath != null)
            $body = '<img src="' . $imgPath . '" alt="' . basename($imgPath) . '"/>'; 

        
        $card = new navCard($href,$body, $label);
}
else 
{
   //if($linkTargetType == 'fragment') 
    //      echo "label: ", $label, "<br/>";
    //    echo "href: ", $href, " body: ", $body, " label: ", $label, "<br/>";
      if(!isset($body) || $body == null)
         $body = '&nbsp;';
        $card = new navCard($href,$body, $label);
}
        $ret .= $card->getOutput('');

        return $ret;
    }

}
?>
