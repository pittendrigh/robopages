<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

/*
 ** grep -i actionItem *php 
 ** Can we and do we want to redirect to layout=gallery if more than N links?
 ** perhaps during gatherlinks()
 ** ...output is buffered and delayed so perhaps possible
 */

class dynamicNavigation extends plugin
{
   protected $linkshash; // all links as object, keyed by href
   protected $fileKeys;  // not hashed array for link ordering

   protected $currentDirPath;
   protected $currentDirUrl;
   protected $currentClickDirUrl;

    // if this dynamicNavigation group includes a slideshow in any position we will hard-code it as the first link
    protected $slideshowFlag;
    protected $mimer;


    function init()
    {
        //echo "dynamicNavigation init <br/>";
        $this->linkshash = array();
        $this->fileKeys = array();
        $this->imageKeys = array();
        $this->dirKeys = array();
        $this->mimer = new roboMimeTyper();

        $this->currentDirPath = $_SESSION['currentDirPath'];
        $this->currentDirUrl = $_SESSION['currentDirUrl'];
        $this->currentClickDirUrl = $_SESSION['currentClickDirUrl'];

        $this->gatherLinks();
    }

    // this marries a label to a thumbNail for link labels using roboresources/thumbs/tn-whatever.jpg 
    function thumbMemer($img, $label)
    {
        $ret = '';
        $ret .= ' <p class="thumbImg">' . $img . '</p>' . "\n";
        $ret .= ' <p class="thumblbl">' . $label . '</p>';
        //$ret .= '</div>' . "\n";
        return $ret;
    }

    function mkLink($link, $LinkTargetType=null)
    {
        global $sys_thumb_links, $sys_show_suffixes;
        $ret = '';

        $highlightFlag=FALSE;
        $hrefComparitor  = preg_replace("/^.*=/", "", $link->href);
        //echo "href: ", $hrefComparitor, "<br/>";
        if(isset($_GET['robopage']) && $hrefComparitor  == $_GET['robopage'])
        {
            $highlightFlag=TRUE;
        }

        // get a default linklbl
        $linklbl = StaticRoboUtils::mkLabel($link->label);
        if (isset($sys_show_suffixes) && $sys_show_suffixes == FALSE)
            $linklbl = StaticRoboUtils::stripSuffix($linklbl);
        $linkTargetType = $link->linkTargetType;

        if ($linkTargetType == 'dir')
        {
            $linklbl = '<i class="material-icons" style="font-size: 80%; ">folder</i> ' .  $linklbl;
        }
        else if ($linkTargetType == 'label')
        {
            $dbg = trim(file_get_contents($this->currentDirPath . $link->label));
            $linklbl = '<p class="tocLabel">' . $dbg . '</p>';
        }
        else if ($linkTargetType == 'image' && $sys_thumb_links)
        {
            $query = parse_url($link->href, PHP_URL_QUERY);
            parse_str($query, $parms);
            if (isset($parms['robopage']))
            {
                $base = basename($parms['robopage']);
                $tpath = $this->currentDirPath . 'roboresources/thumbs/tn-' . $base;

                if (@stat($tpath))
                {
                    $thumb = '<img src="' . $this->currentClickDirUrl . "roboresources/thumbs/tn-" . $base . '" alt="' . $linklbl . '"/>';
                    $linklbl = $this->thumbMemer($thumb, $linklbl);
                }
            }
        }

        if ($linkTargetType == 'label')
        {
            $ret = $linklbl;
        }
        else
        {
          if($highlightFlag) 
          {
            $ret .= "\n" . '<a class="highlighted" href="' . $link->href . '"><b>' . $linklbl . '</b></a>' . "\n";
          }
          else
          {
            if($linkTargetType == 'image')
              $ret .= "\n" . '<div class="thumbMeme"><a href="' . $link->href . '">' . $linklbl . ' </a></div>' . "\n";
            else
              $ret .= "\n" . '<a href="' . $link->href . '">' . $linklbl . ' </a>' . "\n";
          }
        }
        return $ret;
    }

    function getSlideshowLink()
    {
          //$ret = "\n" . '<div class="'.get_class($this).'"><a class="slideshow" href="?robopage='
           //        . $this->currentDirUrl . '&amp;layout=slideshow">Slideshow</a></div>' . "\n";
          $ret = "\n" . '<a class="slideshow" href="?robopage='
                   . $this->currentDirUrl . '&amp;layout=slideshow">Slideshow</a>' . "\n";
          return ($ret);
    }

    function getOutput($divid)
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $indexFlag = FALSE;
        $slideshowFlag = FALSE;
        $indexHref = '';

        $ret = '';

        $cnt = count($this->linkshash);

        if (!$slideshowFlag && @stat($this->currentDirPath . 'roboresources/slideshow'))
        {
            $slideshowFlag = TRUE;
            if( $slideshowFlag )
              $ret .= $this->getSlideshowLink();
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


$allOfEm = array_merge($this->dirKeys,$this->fileKeys,$this->imageKeys);
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


    function getDirlinksPath()
    {
      $path = $this->currentDirPath . "dirlinks";
      return $path;
    }

    function getLines($path)
    {
      $ret = null;
      if(@stat($path))
        $ret = file ($path);
      return $ret;
    }

    function read_dirlinks_file()
    {

        $path = $this->getDirlinksPath();
        $lines = $this->getLines($path); 
        $dirlinksCnt = 0;
        if($lines != null)
          $dirlinksCnt = count($lines);
        for ($j = 0; $j < $dirlinksCnt; $j++)
        {
            $aline = $lines[$j];
            $file = $ordered_hrefKey = '';
            $tokens = explode("::", $aline);
 
            $ordered_hrefKey = trim($tokens[0]);
            $file = $label = $ordered_hrefKey;
            if(isset($tokens[1]))
               $file = $label = trim($tokens[1]);
 
            $linkTargetType = $this->mimer->getRoboMimeType($ordered_hrefKey);
            $linkline = $ordered_hrefKey . "::" . $label . "::$linkTargetType";
            $link = new Link($linkline);
 
            //echo $ordered_hrefKey." ". $linkTargetType. "<br/>";
            if ($linkTargetType == 'dir')
                $this->fileKeys[] = $ordered_hrefKey;
            else if ($linkTargetType == 'image')
                $this->imageKeys[] = $ordered_hrefKey;
            else
                $this->fileKeys[] = $ordered_hrefKey;
 
            $this->linkshash[$ordered_hrefKey] = $link;
        }
        
    }

    // grep -iH "actionItem" *php
    // A now deleted file might leave a link in dirlinks, which is preserved in this system.
    // !!!!  need to add a stat somewhere?
    //

    function lookWhereForFiles()
    {
        // oops no opendir error handling grep -i actionItem *php
        $handle = @opendir($this->currentDirPath);
        return ($handle);
    }

    function find_additional_filenames()
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $linkTargetType = "unknown";

        //$handle = @opendir($this->currentDirPath);
        $handle = $this->lookWhereForFiles();
        while ($handle && ($file = @readdir($handle)) !== FALSE)
        {
            if ($file[0] == '.')
                continue;
            else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
                continue;

            // why not a link?
            //if (is_link($this->currentDirPath . $file)) { continue; }

            $label = ucfirst($file);
            if (!$sys_show_suffixes)
                $label = ucfirst(StaticRoboUtils::stripSuffix($file));

            $linkTargetType = $this->mimer->getRoboMimeType($this->currentDirPath . $file);

            $hrefKey = '';
            if (isset($linkTargetType) && $linkTargetType != "unknown")
            {
                $hrefKey = '?robopage=' . StaticRoboUtils::fixroboPageEqualParm($this->currentDirUrl . $file);

                if ($linkTargetType == 'link')
                {
                    $hrefKey = $this->currentClickDirUrl . $file;
                }
                else if ($linkTargetType == "url")
                { // a url file is a special robopages file name whatever.url that has one or two lines.
                    // second line (if exists) is the label. First is the href.  
                    $rfile = $this->currentDirPath . $file;
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
                    $dbg = trim(file_get_contents($this->currentDirPath . $file));
                    $linklbl = '<p class="tocLabel">' . $dbg . '</p>';
                    $hrefKey = $file;
                }
                else
                {
                    //default and most common case
                    $hrefKey = '?robopage=' . StaticRoboUtils::fixroboPageEqualParm($this->currentDirUrl . $file);
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
    }

}
?>
