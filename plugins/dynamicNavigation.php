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
    public
            $linkshash; // all links as object, keyed by href
    public
            $fileKeys;  // not hashed array for link ordering
    // if this dynamicNavigation group includes a slideshow in any position we will hard-code it as the first link
    public
            $slideshowFlag;
    public
            $mimer;

    function __construct()
    {
        $this->linkshash = array();
        $this->fileKeys = array();
        $this->imageKeys = array();
        $this->dirKeys = array();
        $this->mimer = new roboMimeTyper();
        $this->init();
        $this->gatherLinks();
    }

    // this marries a label to a thumbNail for link labels using roboresources/thumbs/tn-whatever.jpg 
    function thumbMemer($img, $label)
    {
        $ret = '';
        $ret = ' <div class="thumbMeme">' . "\n";
        $ret .= ' <p class="thumbImg">' . $img . '</p>' . "\n";
        $ret .= ' <p class="thumblbl">' . $label . '</p>';
        $ret .= '</div>' . "\n";
        return $ret;
    }

    function mkLink($link)
    {
        global $sys_thumb_links, $sys_show_suffixes;
        $ret = '';
        // get a default linklbl
        $linklbl = StaticRoboUtils::mkLabel($link->label);
        if (isset($sys_show_suffixes) && $sys_show_suffixes == FALSE)
            $linklbl = StaticRoboUtils::stripSuffix($linklbl);
        $linkTargetType = $link->linkTargetType;

        if ($linkTargetType == 'dir')
        {
            $linklbl = '<img class="icon" src="'
                    . $_SESSION["prgrmUrlRoot"] . 'systemimages/folder.png" alt="folder"/>'
                    . '<p class="dnavLbl">' . $linklbl . '</p>';
        }
        else if ($linkTargetType == 'label')
        {
            $dbg = trim(file_get_contents($_SESSION['currentDirPath'] . $link->label));
            $linklbl = '<p class="tocLabel">' . $dbg . '</p>';
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
        //else{ echo "huh: ". $link->href, "<br/>"; exit; }

        if ($linkTargetType == 'label')
        {
            $ret = $linklbl;
        }
        else
            $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";
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
$allOfEm = array_merge($this->dirKeys,$this->imageKeys,$this->fileKeys);
foreach($allOfEm as $aKey)
{

                $link = $this->linkshash[$aKey];
                $ret .= "\n" . $this->mkLink($link) . "\n";
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
                $file = $ordered_hrefKey = '';
                $tokens = explode("::", $aline);

                $ordered_hrefKey = trim($tokens[0]);
                $file = $label = trim($tokens[1]);

                $linkTargetType = $this->mimer->getRoboMimeType($ordered_hrefKey);
                $linkline = $ordered_hrefKey . "::" . $label . "::$linkTargetType";
                $link = new Link($linkline);

                if ($linkTargetType == 'dir')
                    $this->fileKeys[] = $ordered_hrefKey;
                else if ($linkTargetType == 'image')
                    $this->imageKeys[] = $ordered_hrefKey;
                else
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
                $label = ucfirst(StaticRoboUtils::stripSuffix($file));

            $linkTargetType = $this->mimer->getRoboMimeType($_SESSION['currentDirPath'] . $file);

            $hrefKey = '';
            if (isset($linkTargetType) && $linkTargetType != "unknown")
            {
                $hrefKey = '?robopage=' . StaticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);

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
                    $dbg = trim(file_get_contents($_SESSION['currentDirPath'] . $file));
                    $linklbl = '<p class="tocLabel">' . $dbg . '</p>';
                    $hrefKey = $file;
                }
                else
                {
                    //default and most common case
                    $hrefKey = '?robopage=' . StaticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
                }

                // Now test if already already exists from a pre-existing dirlinks file
                // If not we'll add this link, which must be a file added since dirlinks was created
                $atest = @$this->linkshash[$hrefKey];
                if (!isset($atest) || $atest == null)
                {
                    $rline = $hrefKey . '::' . $file . "::$linkTargetType";
                    $link = new Link($rline);
                    $this->linkshash[$hrefKey] = $link;
                    if ($linkTargetType == 'dir')
                        $this->dirKeys[] = $hrefKey;
                    else if ($linkTargetType == 'image')
                        $this->imageKeys[] = $hrefKey;
                    else
                        $this->fileKeys[] = $hrefKey;
                }
            }
        }
        //}
    }

}
?>
