<?php

@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

/*
 * * grep -i actionItem *php 
 * * Can we redirect to layout=gallery if more than 10 links?
 * * perhaps during gatherlinks()
 */

class dynamicNavigation extends plugin
{

    protected $linkshash; // all links as object, keyed by href
    // numerical arrays preserving link name ordering
    var $imageKeys;
    var $dirKeys;
    var $fileKeys;
    // if this dynamicNavigation group includes a slideshow in any position we'll hard-code it as the first link
    var $slideshowFlag;
    protected $mimer;

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

    /*
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
     */

    function thumbMemer($img, $label)
    {
        $ret = '';
        $ret = ' <div class="thumbMeme">' . "\n";
        $ret .= ' <p class="thumbImg">' . $img . '</p>';
        $ret .= "\n" . ' <p class="thumblbl">' . $label . '</p>';

        $ret .= '</div>' . "\n";
        return $ret;
    }

    function mkLink($link, $linkTargetType)
    {
        global $sys_thumb_links;
        $ret = "\n" . '<div class="' . get_class($this) . '">';

        // get a default linklbl
        $linklbl = staticRoboUtils::mkLabel($link->label);

        if ($linkTargetType == 'dir')
        {
            $linklbl = '<img class="' . get_parent_class($this) . ' icon" src="' . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>'
                    . '<p class="dnavLbl">' . $linklbl . '</p>';
        } else if ($linkTargetType == 'image' && $sys_thumb_links)
        {
            $query = parse_url($link->href, PHP_URL_QUERY);
            parse_str($query, $parms);
            if (isset($parms['robopage']))
            {
                $base = basename($parms['robopage']);
                $tpath = $_SESSION['currentDirPath'] . 'roboresources/thumbs/tn-' . $base;

                if (@stat($tpath))
                {
                    $thumb = '<img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . $linklbl . '"/>';
                    $linklbl = $this->thumbMemer($thumb, $linklbl);
                }
            }
        }
        $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";
        return $ret . '</div>';
        //return $ret;
    }

    function getOutput($divid)
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $indexFlag = FALSE;
        $slideshowFlag = FALSE;
        $indexHref = '';


        $ret = '';

        $cnt = count($this->linkshash);

        if (!$slideshowFlag && @stat($_SESSION['currentDirPath'] . 'roboresources/slideshow'))
        {
            $slideshowFlag = TRUE;
            $ret .= "\n" . '<div class="galleryNavigation"><a class="slideshow" href="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=slideshow">Slideshow</a></div>' . "\n";
        }

        $dcnt = count($this->dirKeys);
        $icnt = count($this->imageKeys);
        $fcnt = count($this->fileKeys);

        for ($i = 0; $i < $dcnt; $i++)
        {
            $akey = $this->dirKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
            {
                $ret .= "\n" . $this->mkLink($link, "dir") . "\n";
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
            if ($link != null && !strstr($link->href, "slideshow"))
                $ret .= "\n" . $this->mkLink($link, "file") . "\n";
        }

        for ($i = 0; $i < $icnt; $i++)
        {
            $akey = $this->imageKeys[$i];
            $link = $this->linkshash[$akey];
            $ret .= "\n" . $this->mkLink($link, 'image') . "\n";
        }

        if ($indexFlag)
        {
            $ret .= "\n" . $this->mkLink($indexLink, "file") . "\n";
        }

        return $ret;
    }

    function gatherLinks()
    {
        $this->read_dirlinks_file();
        $this->find_additional_filenames();
    }

    // here we read the optional and maybe non-existant dirlinks file
    // href::label::optionalGuiHnint
    // ?robopage=Driftboats::Boats::dir
    function read_dirlinks_file()
    {
        $path = $_SESSION['currentDirPath'] . "dirlinks";
        if (@stat($path))
        {
            $lines = file($path);
            $dirlinksCnt = count($lines);
            for ($j = 0; $j < $dirlinksCnt; $j++)
            {
                $aline = $lines[$j];
                //echo $aline, "<br/>";
                $file = $ordered_hrefKey = '';
                $tokens = explode("::", $aline);

                $ordered_hrefKey = $tokens[0];

                // skip over any deprecated slideshow links. We'll find them with find_additional_filenames
                //if (strstr($ordered_hrefKey, "slideshow"))
                if ($file == 'slideshow')
                    continue;

                $label = $tokens[1];

                $ordHrefBits = explode("robopage=", $ordered_hrefKey);

                $linkline = $ordered_hrefKey . "::" . $label;

                $link = new Link($linkline);

                $linkType = "unknown";
                $query = parse_url($link->href, PHP_URL_QUERY);
                parse_str($query, $parms);
                if (isset($parms['robopage']))
                {
                    $tpath = $_SESSION['currentDirPath'] . basename($parms['robopage']);
                    if (is_dir($tpath))
                        $linkType = 'dir';
                    else
                        $linkType = $this->mimer->getRoboMimeType($parms['robopage']);
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

    // grep -iHn "actionItem" *php
    // A now deleted file might leave a link in dirlinks, which is preserved in this system.
    // !!!!  need to add a stat somewhere?
    //
    function find_additional_filenames()
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $linkTargetType = "unknown";
        // the next "if" should be superfluous. But the lack of it does keep byting me
        if (!strstr($_SESSION['currentDirPath'], "slideshow") && !strstr($_SESSION['currentDirPath'], 'roboresources'))
        {
            $handle = @opendir($_SESSION['currentDirPath']);
            while ($handle && ($file = @readdir($handle)) !== FALSE)
            {
                if ($file[0] == '.')
                    continue;
                else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
                    continue;
                if (is_link($_SESSION['currentDirPath'] . $file))
                {
                    continue;
                }

                $label = ucfirst($file);
                if (!$sys_show_suffixes)
                    $label = ucfirst(staticRoboUtils::stripSuffix($file));

                $linkTargetType = $this->mimer->getRoboMimeType($_SESSION['currentDirPath'] . $file);

                //if($linkTargetType == 'dir')
                // dirlinks files might have external URLs. But when we read the file system everything is an interal Robopages link
                if (isset($linkTargetType) && $linkTargetType != "unknown")
                {
                    $hrefKey = '?robopage=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                    if ($linkTargetType == 'link')
                    {
                        $hrefKey = $_SESSION['currentClickDirUrl'] . $file;
                    } else if ($linkTargetType == "url")
                    { // a url file is a special robopages file name whatever.url that has one or two lines.
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
                    } else if ($hrefKey[0] == '#' || $linkTargetType == "lbl")
                    {
                        $hrefKey = '#';
                        $label = staticRoboUtils::mkLabel(staticRoboUtils::stripSuffix($file));
                    } else
                    { //default and most common case
                        if ($file == "slideshow" && $this->slideshowFlag)
                        {
                            $hrefKey = '?robopage=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file) . '&amp;layout=slideshow';
                        } else
                        {
                            $hrefKey = '?robopage=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                        }
                    }

                    // And now we can test to see if we already have this links from a pre-existing dirlinks file
                    // If not we'll add this link, which must be a file added since dirlinks was created
                    // So we'll append it to the links system
                    $atest = @$this->linkshash[$hrefKey];
                    if (!isset($atest) || $atest == null)
                    {
                        $rline = $hrefKey . '::' . $label;
                        if ($linkTargetType != null)
                            $rline .= '::' . $linkTargetType;
                        $link = new Link($rline);
                        $this->linkshash[$hrefKey] = $link;

                        if ($linkTargetType == 'dir')
                            $this->dirKeys[] = $hrefKey;
                        else if ($linkTargetType == 'image')
                            $this->imageKeys[] = $hrefKey;
                        else
                        {
                            $this->fileKeys[] = $hrefKey;
                        }
                    }
                }
            }
        }
    }

}

?>
