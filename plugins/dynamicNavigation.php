<?php

@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

/*
 * * grep -i actionItem *php 
 * * Can we and dow we want to redirect to layout=gallery if more than N links?
 * * perhaps during gatherlinks()
 * * ...output is buffered and delayed so would be possible
 */

class dynamicNavigation extends plugin
{

    public $linkshash; // all links as object, keyed by href
    public $fileKeys;  // not hashed array for link ordering

    // if this dynamicNavigation group includes a slideshow in any position we will hard-code it as the first link
    public $slideshowFlag;
    public $mimer;

    function __construct()
    {
        $this->linkshash = array();
        $this->fileKeys = array();
        $this->mimer = new roboMimeTyper();
        $this->init();
        $this->gatherLinks();
    }

    /*

      // this can make the link for an  index.htm fragment have a thumb icon
      // ...which can lead to visual confusion.  
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
      }
     */

    // this marries a label to a thumbNail for link labels using roboresources/thumbs/tn-whatever.jpg 
    function thumbMemer($img, $label)
    {
        $ret = '';
        $ret = ' <div class="thumbMeme">' . "\n";
        $ret .= ' <p class="thumbImg">' . $img   . '</p>' . "\n";
        $ret .= ' <p class="thumblbl">' . $label . '</p>';
        $ret .= '</div>' . "\n";
        return $ret;
    }

    function mkLink($link)
    {
        global $sys_thumb_links;

        /// This div get_class is a bad idea and if so why?  Like CSS downcasting? For galleryNav differences?
        /// How else to do this?
        /// grep -iH actionItem *php
        //$ret = "\n" . '<div class="' . get_class($this) . '">';
        $ret = '';

        // get a default linklbl
        $linklbl = staticRoboUtils::mkLabel($link->label);
        $linkTargetType = $link->linkTargetType;

        if ($linkTargetType == 'dir')
        {
            $linklbl = '<img class="' . get_parent_class($this) . ' icon" src="' 
                       . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>'
                       . '<p class="dnavLbl">' . $linklbl . '</p>';
        } 
        else if ($linkTargetType == 'label')
        {
           $dbg = trim(file_get_contents($_SESSION['currentDirPath'].$link->label));
           $linklbl = '<p class="tocLabel">' .$dbg . '</p>';
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
                    $thumb = '<img src="' . $_SESSION['currentClickDirUrl'] . "roboresources/thumbs/tn-" . $base . '" alt="' . $linklbl . '"/>';
                    $linklbl = $this->thumbMemer($thumb, $linklbl);
                }
            }
        }
        if($linkTargetType == 'label')
        {
            $ret = $linklbl;
        }
        else
            $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";
        //return $ret . '</div>';
        return $ret;
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
            // hard-coding again....
            $ret .= "\n" . '<div class="galleryNavigation"><a class="slideshow" href="?robopage=' 
                 . $_SESSION['currentDirUrl'] . '&amp;layout=slideshow">Slideshow</a></div>' . "\n";
        }

        // fileKeys was made in the ctor
        $fcnt = count($this->fileKeys);

        for ($i = 0; $i < $fcnt; $i++)
        {
            $akey = $this->fileKeys[$i];
            $link = $this->linkshash[$akey];
            if ($link != null && !strstr($link->href, "slideshow"))
            {
                $ret .= "\n" . $this->mkLink($link) . "\n";
            }
        }

        // any index link like ?robopage=index.htm or index.jpg made to come last
        if ($indexFlag)
        {
            $ret .= "\n" . $this->mkLink($indexLink) . "\n";
        }

        return $ret;
    }

    function gatherLinks()
    {
        $this->read_dirlinks_file();
        $this->find_additional_filenames();
    }

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
                $file=$ordered_hrefKey = '';
                $tokens = explode("::", $aline);

                $ordered_hrefKey = trim($tokens[0]);
                $file = $label = trim($tokens[1]);
                //echo "file: ", $file, " orderedHrefKey: ". $ordered_hrefKey, "<br/>";

                $linkTargetType = $this->mimer->getRoboMimeType($ordered_hrefKey);
                $linkline = $ordered_hrefKey . "::" . $label . "::$linkTargetType";

                $link = new Link($linkline);
                $this->fileKeys[] = $ordered_hrefKey;
                $this->linkshash[$ordered_hrefKey] = $link;
            }
        }
    }

    // grep -iH "actionItem" *php
    // A now deleted file might leave a link in dirlinks, which is preserved in this system.
    // !!!!  need to add a stat somewhere?
    //
    function find_additional_filenames()
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $linkTargetType = "unknown";

        // the next "if" should be superfluous. But the lack of it does keep byting me
        //if (!strstr($_SESSION['currentDirPath'], 'roboresources'))
        //{
            $handle = @opendir($_SESSION['currentDirPath']);
            while ($handle && ($file = @readdir($handle)) !== FALSE)
            {
                if ($file[0] == '.')
                    continue;
                else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
                    continue;

                // why not a link?
                if (is_link($_SESSION['currentDirPath'] . $file))
                {
                    continue;
                }

                $label = ucfirst($file);
                if (!$sys_show_suffixes)
                    $label = ucfirst(staticRoboUtils::stripSuffix($file));

                $linkTargetType = $this->mimer->getRoboMimeType($_SESSION['currentDirPath'] . $file);

                $hrefKey='';
                if (isset($linkTargetType) && $linkTargetType != "unknown")
                {
                    $hrefKey = '?robopage=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                    
                    if ($linkTargetType == 'link')
                    {
                        $hrefKey = $_SESSION['currentClickDirUrl'] . $file;
                    } 
                    else if ($linkTargetType == "url")
                    { // a url file is a special robopages file name whatever.url that has one or two lines.
                        // second line (if exists) is the label. First is the href.  
                        $rfile = $_SESSION['currentDirPath'] . $file;
                        $lines = file($rfile);
                        $hrefKey = trim($lines[0]);
                        $label = $hrefKey;
                        if (isset($lines[1]))
                        {
                            $label = $lines[1];
                        }
                    } 
                    else if ($linkTargetType == "label")
                    {
                         $dbg = trim(file_get_contents($_SESSION['currentDirPath'].$file));
                         $linklbl = '<p class="tocLabel">' .$dbg . '</p>';
                         $hrefKey = $file; 
                    }
                    else
                    { 
                        //default and most common case
                        $hrefKey = '?robopage=' . staticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                    }

                    // Now test if already already exists from a pre-existing dirlinks file
                    // If not we'll add this link, which must be a file added since dirlinks was created
                    $atest = @$this->linkshash[$hrefKey];
                    if (!isset($atest) || $atest == null)
                    {
                        $rline = $hrefKey . '::' . $file . "::$linkTargetType";
                        $link = new Link($rline);
                        $this->linkshash[$hrefKey] = $link;
                        //echo "stuffing: ", $hrefKey, "<br/>";
                        $this->fileKeys[] = $hrefKey;
                    }
                }
            }
        //}
    }
}

?>
