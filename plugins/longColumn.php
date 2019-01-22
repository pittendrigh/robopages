<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("dynamicNavigation.php");

class longColumn extends dynamicNavigation
{
    protected
            $currentDirPath;
    protected
            $currentClickDirUrl;

    function thumbMemer($img, $label)
    {
        $ret = '';
        $ret = ' <div class="thumbMeme">' . "\n";
        $ret .= ' <p class="thumbImg">' . $img . '</p>' . "\n";
        $ret .= ' <p class="thumblbl">' . $label . '</p>';
        $ret .= '</div>' . "\n";
        return $ret;
    }

    //mkLink in parent class is similar but it uses Link object
    function mkHref($file, $linkTargetType)
    {
        $hrefKey = '';

        //echo "mkHref: $file, $linkTargetType <br/>";
        if ($linkTargetType == 'link')
        {
            $hrefKey = $this->currentClickDirUrl . $file;
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
        else if ($linkTargetType == "lcm")
        { // an lcm file is a url like file pointint to a txt blog entry.
            // second line (if exists) is the label. First is the href.  
            $rfile = $_SESSION['currentDirPath'] . $file;
            $lines = file($rfile);
            $hrefKey = '?layout=blog&amp;blogFilename=' . trim($lines[0]);
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
        else if ($linkTargetType == "blurb")
        {
            $dbg = trim(file_get_contents($_SESSION['currentDirPath'] . $file));
            $linklbl = '<p class="tocLabel">' . $dbg . '</p>';
            $hrefKey = $file;
        }
        else
        {
            $hrefKey = '?robopage=' . StaticRoboUtils::fixPageEqualParm($_SESSION['currentDirUrl'] . $file);
        }

        return $hrefKey;
    }

    function subDirLinks($path)
    {
        $ret = array();

        $handle = @opendir($path);
        while ($handle && ($file = @readdir($handle)) !== FALSE)
        {
            $linkTargetType = $linkTargetType = $this->mimer->getRoboMimeType($file);
            if ($file[0] == '.')
                continue;
            $ret[] = $file;
        }
        return $ret;
    }

    function getOutput($divid)
    {
        global $sys_show_suffixes, $sys_thumb_links;

        $indexFlag = FALSE;
        $indexHref = '';

        $ret = '';

        $cnt = count($this->linkshash);

        // fileKeys was made in the ctor
        $dcnt = count($this->dirKeys);
        $icnt = count($this->imageKeys);
        $fcnt = count($this->fileKeys);

        for ($i = 0; $i < $dcnt; $i++)
        {
            $akey = $this->dirKeys[$i];
            $link = $this->linkshash[$akey];
            $ret .= '<h2>' . $link->label . "</h2>";

            $subFiles = null;
            // how reliable is label as filename...does Link need another attribute?
            $subPath = $this->currentDirPath . $link->label . '/';
            //echo "subPath: ", $subPath, "<br/>";
            $subFiles = $this->subDirLinks($subPath);
            $jcnt = count($subFiles);
            for ($j = 0; $j < $jcnt; $j++)
            {
                $contentFile = $subPath . $subFiles[$j];
                $linkTargetType = $this->mimer->getRoboMimeType($contentFile);

                if ($linkTargetType == 'blog')
                {
                    $filePieces = file($contentFile);
                    $subLabel = $filePieces[1];
                    $tmpDestinationString = trim($filePieces[0]);
                    $subContent = '<p class="longCLabel"><a href="?layout=blog&amp;blogFilename=' . $tmpDestinationString . '">' . $subLabel . '</a></p>';
                }
                if ($linkTargetType == 'lcm')
                {
                    $filePieces = file($contentFile);
                    $subLabel = $filePieces[1];
                    $tmpDestinationString = '?blogFilename=' . trim($filePieces[0]);
                    $subContent = '<p class="longCLabel"><a href="?layout=blog&amp;blogFilename=' . $tmpDestinationString . '">' . $subLabel . '</a></p>';
                }
                else if ($linkTargetType == 'blurb')
                {
                    $tmpContent = file_get_contents($contentFile);
                    $subContent = '<p class="longCLabel">' . $tmpContent . '</p>';
                }
                else
                {
                    $filePieces = file($contentFile);
                    $subLabel = $filePieces[1];
                    $tmpDestinationString = trim($filePieces[0]);

                    $subContent = '<p class="longCLabel"><a href="' . $tmpDestinationString . '">' . $subLabel . '</a></p>';
                }
                $ret .= $subContent;
            }
        }

        for ($i = 0; $i < $icnt; $i++)
        {
            $akey = $this->imageKeys[$i];
            //echo "icnt $i $akey <br/>";
            $link = $this->linkshash[$akey];
            if ($link != null)
            {
                $ret .= "\n" . $this->mkLink($link) . "\n";
            }
        }

        for ($i = 0; $i < $fcnt; $i++)
        {

            $akey = $this->fileKeys[$i];
            $link = $this->linkshash[$akey];
            //echo "fcnt $i $akey <br/>";
            if ($link != null)
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

        $this->currentDirPath = $_SESSION['currentDirPath'] . 'roboresources/longColumn/';
        if (!@stat($this->currentDirPath))
            $this->currentDirPath = getcwd() . '/fragments/roboresources/longColumn/';

        $this->currentClickDirUrl = $_SESSION['currentClickDirUrl'] . 'fragments/roboresources/longColumn/';
        if (!@stat($this->currentClickDirUrl))
            $this->currentClickDirUrl = $_SESSION['prgrmUrlRoot'] . 'fragments/roboresources/longColumn/';

        //echo "currentDirPath: ", $this->currentDirPath, "<br/>";
        //echo "currentClickDirUrl: ", $this->currentClickDirUrl, "<br/>";

        $this->read_dirlinks_file();
        $this->find_additional_filenames();
    }

    // make this assume a file name only in dirlinks, followed by optional label
    // if no label make a default one from the filename
    function read_dirlinks_file()
    {
        if (@stat($this->currentDirPath . 'dirlinks'))
        {
            $lines = file($this->currentDirPath . 'dirlinks');
            $dirlinksCnt = count($lines);
            for ($j = 0; $j < $dirlinksCnt; $j++)
            {
                $aline = $lines[$j];
                $file = $ordered_hrefKey = '';
                $tokens = explode("::", $aline);

                $label = $file = trim($tokens[0]);
                $test = trim($tokens[1]);
                if (isset($test))
                    $label = trim($tokens[1]);

                $linkTargetType = $this->mimer->getRoboMimeType($this->currentDirPath . $file);
                $hrefKey = $this->mkHref($file, $linkTargetType);

                // we could determine $linkTargetType from file at any time
                // storing it accessibly in the Link object is a convenience 
                $linkline = $hrefKey . "::" . $label . "::$linkTargetType";
                $link = new Link($linkline);

                if ($linkTargetType == 'dir')
                    $this->fileKeys[] = $hrefKey;
                else if ($linkTargetType == 'image')
                    $this->imageKeys[] = $hrefKey;
                else
                    $this->fileKeys[] = $hrefKey;

                $this->linkshash[$hrefKey] = $link;
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

        $handle = @opendir($this->currentDirPath);
        //echo "find_additional_filenames this->currentDirPath: ", $this->currentDirPath, "<br/>";

        while ($handle && ($file = @readdir($handle)) !== FALSE)
        {
            if ($file[0] == '.')
                continue;
            else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
                continue;

            // why not a link?
            // if (is_link($_SESSION['currentDirPath'] . $file)) { continue; }

            $label = ucfirst($file);
            //if (!$sys_show_suffixes)
            //   $label = ucfirst(StaticRoboUtils::stripSuffix($file));
            //echo "huh: ".$this->currentDirPath . $file."<br/>";
            $linkTargetType = $this->mimer->getRoboMimeType($this->currentDirPath . $file);
            //echo "find_additional_filenames file: $file $linkTargetType <br/>"; 

            $hrefKey = $this->mkHref($file, $linkTargetType);

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
?>
