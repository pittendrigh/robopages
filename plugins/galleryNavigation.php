<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("dynamicNavigation.php");

class galleryNavigation extends dynamicNavigation
{

    function lookWhereForFiles()
    {
        // oops no opendir error handling grep -i actionItem *php
        $handle = @opendir($this->currentDirPath . 'roboresources/pics');
        return ($handle);
    }


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
                $ret = '<img src="' . $thumbUrl . '" alt="' . $base . '"/>';
            }
        }

        return $ret;
    }

    function mkLink($link, $LinkTargetType=null)
    {
        global $sys_thumb_links;
        $ret = "\n\n" . '<div class="' . get_class($this) . '">' . "\n";
        $ret .= '<a href="' . $link->href . '">';

        // get a default linklbl
        $linklbl = StaticRoboUtils::mkLabel($link->label);

        $linkTargetType = $link->linkTargetType;

        if ($linkTargetType == 'dir')
        {

            $indexImageTest = $this->hasIndexImg($link);

            if ($indexImageTest != null)
            {
                $linklbl = $linklbl . '<br/>' . $indexImageTest;
            }
            else
                $linklbl = "\n" . '<i class="material-icons" style="font-size: 80%; ">folder</i>' . $linklbl;
        }

        // grep -iH actionItem *php which of the follwing ifs?
        //else if ($linkTargetType == 'image' && $sys_thumb_links && strstr($link->href, 'robopage=')) 
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
                    $thumb = '<img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . 'xxx' . '"/>';
                    //echo htmlentities($linklbl), "<br/>";
                    $linklbl = "\n" . $this->thumbMemer($thumb, $linklbl) . "\n";
                }
            }
        }


        $ret .= $linklbl . '</a>' . "\n";
        $ret .= '</div>';
        return $ret;
    }

}
?>
