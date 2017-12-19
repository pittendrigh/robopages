<?php

@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

class dynamicNavigation extends plugin {

    protected $linkshash; // all links as object, keyed by href
    // numerical arrays preserving link name ordering
    var $imageKeys;
    var $dirKeys;
    var $fileKeys;
    // if this dynamicNavigation group includes a slideshow in any position we'll hard-code it as the first link
    var $slideshowFlag;
    protected $mimer;

    function __construct() {
        $this->slideshowFlag = TRUE;
        $this->linkshash = array();
        $this->dirKeys = array();
        $this->fileKeys = array();
        $this->imageKeys = array();
        $this->mimer = new roboMimeTyper();
        $this->init();
        $this->gatherLinks();
    }

    function hasIndexImg($link, $mode = null) {
        $ret = '';
        $thumbName = 'tn-index.jpg';
        // tn-index.jpg needs to be generalized to is_image in index plus suffix
        $query = parse_url($link->href, PHP_URL_QUERY);
        parse_str($query, $parms);
        // we called this from linkTargetType=='dir' so we assume basename is a directory 
        if (isset($parms['page'])) {
            // roboresources becomes resources with next Jul 4, '14 code update
            $base = basename($parms['page']);
            $thumbUrl = $_SESSION['currentClickDirUrl'] . $base . '/roboresources/thumbs/' . $thumbName;
            $testPath = $_SESSION['currentDirPath'] . $base . '/roboresources/thumbs/' . $thumbName;
            if ($mode != null && $mode == 'file') {
                $thumbName = 'tn-' . staticRoboUtils::stripSuffix(basename($parms['page'])) . '.jpg';
                $testPath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/' . $thumbName;
                $thumbUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/thumbs/' . $thumbName;
                //echo "new thumbName: ", $thumbName, '<br/>';
                //echo "testPath: ", $testPath, '<br/>';
            }
            //echo "testPath: ", $testPath, "<br/>";
            $test = @stat($testPath);
            if ($test != null) {
                //echo "testPath: ", $testPath, "<br/>";
                $ret = '<img src="' . $thumbUrl . '" alt="' . $base . '"/>';
                //echo htmlentities($ret), "<br/>";
            }
        }

        return $ret;
    }

    function mkLink($link, $linkTargetType) {
        global $sys_thumb_links;

        $linklbl = staticRoboUtils::mkLabel($link->label);
        $ret = '';

        //echo "linkTargetType: ", $linkTargetType, "<br/>";
        if ($linkTargetType == 'dir') {
            $indexImageTest = $this->hasIndexImg($link);

            if ($indexImageTest != null) {
                $linklbl = $linklbl . '<br/>' . $indexImageTest;
            } else
                $linklbl = '<img class="robonav icon" src="' . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>' . $linklbl;
        }
        else if ($linkTargetType == 'image' && $sys_thumb_links && strstr($link->href, 'page=')) {
            $page = $linkTargetType = '';
            $query = parse_url($link->href, PHP_URL_QUERY);
            parse_str($query, $parms);
            if (isset($parms['page'])) {
                $base = basename($parms['page']);
                $tpath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/tn-' . $base;

                if (@stat($tpath))
                    $linklbl = $linklbl . '<br/><img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . $linklbl . '"/>';
            }
        }
        else if ($linkTargetType == 'file') {
            $possibleThumb = $this->hasIndexImg($link, "file");
            $linklbl = $linklbl . $possibleThumb;

            //echo htmlentities($linklbl), "<br/>";
        }

        $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";

        //$ret = '<p style="clear: both;"> &nbsp; </p>' . $ret;
        return $ret;
    }

    function getOutput($divid) {
        global $sys_show_suffixes, $sys_thumb_links;

        $indexFlag = FALSE;
        $slideshowFlag = FALSE;
        $indexHref = '';


        $ret = $class = '';
        $lbl = '';

        $cnt = count($this->linkshash);

        if (!$slideshowFlag && @stat($_SESSION['currentDirPath'] . 'roboresources/slideshow')) {
            $slideshowFlag = TRUE;
            $ret .= '<p class="robonav"><a class="slideshow" href="?page=' . $_SESSION['currentDirUrl'] . '&amp;layout=slideshow">Slideshow</a></p>';
        }

        $dcnt = count($this->dirKeys);
        $icnt = count($this->imageKeys);
        $fcnt = count($this->fileKeys);

        for ($i = 0; $i < $dcnt; $i++) {
            $akey = $this->dirKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow")) {
                $ret .= '<p class="robonav">' . $this->mkLink($link, "dir") . '</p>';
            }
        }


        for ($i = 0; $i < $fcnt; $i++) {
            $akey = $this->fileKeys[$i];
            if ($akey == 'index.htm') {
                $indexFlag = TRUE;
                $indexLink = $this->linkshash['index.htm'];
                continue;
            }
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
                $ret .= '<p class="robonav">' . $this->mkLink($link, "file") . '</p>';
        }

        for ($i = 0; $i < $icnt; $i++) {
            $akey = $this->imageKeys[$i];
            $link = $this->linkshash[$akey];
            $ret .= '<p class="robonav">' . $this->mkLink($link, 'image') . '</p>';
        }

        if ($indexFlag) {
            $ret .= '<p class="robonav">' . $this->mkLink($indexLink, "file") . '</p>';
        }

        // if ($cnt > 0)
        //    $ret .= '</ul>';
        return $ret;
    }

    function gatherLinks() {
        $this->read_dirlinks_file();
        $this->find_additional_filenames();
    }

    // here we read the optional and maybe non-existant dirlinks file
    // href::label::optionalGuiHint
    // ?page=Driftboats::Boats::dir
    function read_dirlinks_file() {
        $path = $_SESSION['currentDirPath'] . "dirlinks";
        if (@stat($path)) {
            $lines = file($path);
            $dirlinksCnt = count($lines);
            for ($j = 0; $j < $dirlinksCnt; $j++) {
                $aline = $lines[$j];
                $file = $ordered_hrefKey = '';
                $tokens = explode("::", $aline);

                $ordered_hrefKey = $tokens[0];

                // skip over any deprecated slideshow links. We'll find them with find_additional_filenames
                //if (strstr($ordered_hrefKey, "slideshow"))
                if($file == 'slideshow')
                    continue;

                $label = $tokens[1];

                $ordHrefBits = explode("page=", $ordered_hrefKey);

                $linkline = $ordered_hrefKey . "::" . $label;

                $link = new Link($linkline);

                $linkType = "unknown";
                $query = parse_url($link->href, PHP_URL_QUERY);
                parse_str($query, $parms);
                if (isset($parms['page'])) {
                    $tpath = $_SESSION['currentDirPath'] . basename($parms['page']);
                    if (is_dir($tpath))
                        $linkType = 'dir';
                    else
                        $linkType = $this->mimer->getRoboMimeType($parms['page']);
                }
                if ($linkType == 'dir')
                    $this->dirKeys[] = $ordered_hrefKey;
                else if ($linkType == 'image')
                    $this->imageKeys[] = $ordered_hrefKey;
                else if ($linkType != 'unknown')
                    $this->fileKeys[] = $ordered_hrefKey;
                $this->linkshash[$ordered_hrefKey] = $link;
            }
        }
    }

    // grep -H "actionitem" *php
    // A now deleted file might leave a link in dirlinks, which is preserved in this system.
    // !!!!  need to add a stat somewhere?
    function find_additional_filenames() {
        global $sys_show_suffixes, $sys_thumb_links;

        $linkTargetType = "unknown";
        // the next "if" should be superfluous. But the lack of it does keep byting me
        if (!strstr($_SESSION['currentDirPath'], "slideshow") && !strstr($_SESSION['currentDirPath'], 'roboresources')) {
            $handle = @opendir($_SESSION['currentDirPath']);
            while ($handle && ($file = @readdir($handle)) !== FALSE) {
                if ($file[0] == '.')
                    continue;
                else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
                    continue;
                if (is_link($_SESSION['currentDirPath'] . $file)) {
                    continue;
                }

                $label = ucfirst($file);
                if (!$sys_show_suffixes)
                    $label = ucfirst(staticRoboUtils::stripSuffix($file));

                $linkTargetType = $this->mimer->getRoboMimeType($_SESSION['currentDirPath'] . $file);

                //if($linkTargetType == 'dir')
                // dirlinks files might have external URLs. But when we read the file system everything is an interal Robopages link
                if (isset($linkTargetType) && $linkTargetType != "unknown") {
                    $hrefKey = '?page=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                    if ($linkTargetType == 'link') {
                        $hrefKey = $_SESSION['currentClickDirUrl'] . $file;
                    } else if ($linkTargetType == "url") { // a url file is a special robopages file name whatever.url that has one or two lines.
                        // second line (if exists) is the label. First is the href.  
                        $rfile = $_SESSION['currentDirPath'] . $file;
                        $lines = file($rfile);
                        $hrefKey = trim($lines[0]);
                        $label = $hrefKey;
                        if (isset($lines[1]))
                        {
                            //$label = ucfirst(trim($lines[1]));
                            $label = $lines[1];
//echo htmlentities($label);
                        }
                    }
                    else if ($hrefKey[0] == '#' || $linkTargetType == "lbl") {
                        $hrefKey = '#';
                        $label = staticRoboUtils::mkLabel(staticRoboUtils::stripSuffix($file));
                    } else { //default and most common case
                        // need to not hard-code div id="main-content" ...we already have a way grep -H "actionitem" *php
                        if ($file == "slideshow" && $this->slideshowFlag) {
                            $hrefKey = '?page=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file) . '&amp;layout=slideshow';
                        } else {
                            $hrefKey = '?page=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                        }
                    }

                    // And now we can test to see if we already have this links from a pre-existing dirlinks file
                    // If not we'll add this link, which must be a file added since dirlinks was created
                    // So we'll append it to the links system
                    $atest = @$this->linkshash[$hrefKey];
                    if (!isset($atest) || $atest == null) {
                        $rline = $hrefKey . '::' . $label;
                        if ($linkTargetType != null)
                            $rline .= '::' . $linkTargetType;
                        $link = new Link($rline);
                        $this->linkshash[$hrefKey] = $link;

                        if ($linkTargetType == 'dir')
                            $this->dirKeys[] = $hrefKey;
                        else if ($linkTargetType == 'image')
                            $this->imageKeys[] = $hrefKey;
                        else {
                            $this->fileKeys[] = $hrefKey;
                        }
                    }
                }
            }
        }
    }

}

?>
