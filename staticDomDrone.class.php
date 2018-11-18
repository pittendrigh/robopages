<?php

include_once("domDrone.class.php");

class staticDomDrone extends domDrone
{

    function roboLogit($msg)
    {
        //echo "roboLogit ", htmlentities($msg), "<br/>";
        $fp = fopen("/tmp/robolog.log", "a");
        fwrite($fp, $msg . "\n");
        chmod("/tmp/robolog.log", 0777);
        fclose($fp);
    }

    function copyDirContents($source, $dest)
    {

        if (is_dir($source))
        {
            if (!@stat($dest))
            {
                mkdir($dest, 0777);
                chmod($dest, 0777);
            }

            $indexFlag = 0;
            $dir_handle = opendir($source);
            while ($file = readdir($dir_handle))
            {
                if ($file[0] == '.')
                    continue;

                $suffix = strtolower(StaticRoboUtils::getSuffix(basename($file)));
                $allowed = array("css", "html", "txt", "jpg", "png", "gif", "zip", "tgz");
                if (in_array($suffix, $allowed) && !is_dir($source . $file))
                {
                    //$this->roboLogit("copyDirContents copy: " . $source . '/'. $file . " to: ". $dest . '/' . $file);
                    copy($source . '/' . $file, $dest . '/' . $file);
                }
            }
        }
    }

    function copyDirRecursively($source, $dest)
    {
        $indexFlag = 0;
        $allowed = array("css", "html", "txt", "jpg", "png", "gif", "zip", "tgz");

        //$this->roboLogit("copyDirRecursively($source, $dest)");
        if (is_dir($source))
        {
            $dir_handle = opendir($source);
            while ($file = readdir($dir_handle))
            {
                if ($file != "." && $file != "..")
                {
                    if (is_dir($source . "/" . $file))
                    {
                        if (!is_dir($dest . "/" . $file))
                        {
                            mkdir($dest . "/" . $file, 0777);
                            chmod($dest . "/" . $file, 0777);
                        }
                        $this->copyDirRecursively($source . '/' . $file, $dest . '/' . $file);
                    } else
                    {
                        $suffix = strtolower(StaticRoboUtils::getSuffix(basename($file)));

                        //if($suffix != 'htm')
                        if (in_array($suffix, $allowed))
                        {
                            $this->roboLogit("copyDirRecursively copy: " . $source . '/' . $file . " to: " . $dest . '/' . $file);
                            copy($source . '/' . $file, $dest . '/' . $file);
                            chmod($dest . "/" . $file, 0777);
                        }
                    }
                }
            }
            closedir($dir_handle);
        } else
        {
            // grep -i actionItem *php ....ever go here? Log this at some point
            @copy($source, $dest);
            chmod($dest, 0777);
        }
    }

    function Echostatic($ddata, $mode)
    {
        global $sys_static_location;

        $needIndexHtmlFile = TRUE;
        $is_dirTestPath = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];

        if (is_dir($is_dirTestPath))
        {
            $needIndexHtmlFile = TRUE;
        }

        foreach (glob($_SESSION['currentDirPath'] . 'index*') as $filename)
        {
            $needIndexHtmlFile = FALSE;
        }
        $staticDirPath = getcwd() . '/Pages/' . $_SESSION['currentDirUrl'];

        // perhaps there are hand edited links in the data that need to be converted 
        $data = preg_replace('/\?robopage=|index.php\?robopage=/', '', $ddata);
        //echo "data: ", htmlentities($data), "<br/>";


        if (!@stat($staticDirPath))
        {
            mkdir($staticDirPath, 0777, true);
            chmod($staticDirPath, 0777);
        }

        // this copies only allowed files (no *.htm) grep -iH actionItem *php
        // .....need an @stat step or strategy to do this only once per file? 
        $this->copyDirContents($_SESSION['currentDirPath'], $staticDirPath);

        //if (@stat($_SESSION['currentDirPath'] . 'roboresources'))
        if (1 > 0)
        {

            //if (!@stat($staticDirPath . 'roboresources'))
            if (1 > 0)
            {
                @mkdir($staticDirPath . 'roboresources', 0777);
                @chmod($staticDirPath . 'roboresources', 0777);
                $this->copyDirRecursively($_SESSION['currentDirPath'] . 'roboresources/', $staticDirPath . 'roboresources');
            }
        }


        $file = $_SESSION['currentDisplay'] == null ? 'index.html' : StaticRoboUtils::stripSuffix($_SESSION['currentDisplay']) . '.html';

        $filepath = $staticDirPath . $file;

        //$this->roboLogit( "EchoStatic filepath: " . $filepath);
        $fp = fopen($filepath, $mode);
        fwrite($fp, $data);
        fclose($fp);

        if ($needIndexHtmlFile)
        {
            $file = 'index.html';
            $filepath = $staticDirPath . $file;
            $fp = fopen($filepath, $mode);
            fwrite($fp, $data);
            fclose($fp);
        }
    }

    function dotsUp($someUrl)
    {
        $ret = '';

        //$this->roboLogit( "dotsUp someUrl: ". $someUrl);
        $tmpArr = explode("/", $someUrl);
        // hack 
        //$tmpArr = array_slice($tmpArr,1);

        foreach ($tmpArr as $val)
        {
            // fragments dir does not exist in the static version
            // fragments appears in robopages image src attributes but not href attributes
            if ($val && $val != 'fragments')
            {
                $ret .= '../';
            }
        }

        return $ret;
    }

    function relativeHref($dynamicDestUrl)
    {
        $ret = $dynamicDestUrl;
        $currentDirUrl = $_SESSION['currentDirUrl'];

        if (strstr($dynamicDestUrl, "http:"))
            return $dynamicDestUrl;

        // insure not slash at the end of $dynamicDestUrl
        if (substr($dynamicDestUrl, -1) == '/')
            $dynamicDestUrl = substr($dynamicDestUrl, 0, -1);

        // insure not slash at the end of $currentDirUrl
        if (substr($currentDirUrl, -1) == '/')
            $currentDirUrl = substr($currentDirUrl, 0, -1);

        $currentUrlSequence = explode("/", $currentDirUrl);
        $destUrlSequence = explode("/", $dynamicDestUrl);
        $dotsStr = $this->dotsUp($currentDirUrl);
        $dotsArray = explode('/', $dotsStr);
        foreach ($currentUrlSequence as $adir)
        {
            if ($adir && strstr($dynamicDestUrl, $adir))
            {
                $dotsArray = array_slice($dotsArray, 1);
                $destUrlSequence = array_slice($destUrlSequence, 1);
            }
        }

        $newDots = implode('/', $dotsArray);
        $newDest = implode('/', $destUrlSequence);

        $ret = $newDots . $newDest;

        $this->roboLogit("relativeHref ret: " . $ret);
        return $ret;
    }

    ///iiiiiiiiiimg
    function relativeSrc($dynamicImgDestUrl)
    {
        $ret = '';

        //if(strstr($dynamicImgDestUrl,"fragments"))
        $currentDirUrl = $_SESSION['currentClickDirUrl'];
        //else
        //$currentDirUrl = $_SESSION['currentDirUrl'];

        if (strstr($dynamicImgDestUrl, "http:"))
            return $dynamicImgDestUrl;


        // insure not slash at the end of $dynamicImgDestUrl
        if (substr($dynamicImgDestUrl, -1) == '/')
            $dynamicImgDestUrl = substr($dynamicImgDestUrl, 0, -1);
        if (substr($dynamicImgDestUrl, 0, 1) == '/')
            $dynamicImgDestUrl = substr($dynamicImgDestUrl, 1);

        // insure not slash at the end of $currentDirUrl
        if (substr($currentDirUrl, -1) == '/')
            $currentDirUrl = substr($currentDirUrl, 0, -1);
        if (substr($currentDirUrl, 0, 1) == '/')
            $currentDirUrl = substr($currentDirUrl, 1);

        $currentUrlSequence = explode("/", $currentDirUrl);
        $destUrlSequence = explode("/", $dynamicImgDestUrl);
        $dotsStr = $this->dotsUp($currentDirUrl);

        $dotsArray = explode('/', $dotsStr);
        $cnt = count($currentUrlSequence);
        for ($i = 0; $i < $cnt; $i++)
        {
            $adir = $currentUrlSequence[$i];
            if ($dynamicImgDestUrl != null && $adir != null && strstr($dynamicImgDestUrl, $adir))
            {
                $dotsArray = array_slice($dotsArray, 1);
                $dbg = implode("/", $destUrlSequence);
                $destUrlSequence = array_slice($destUrlSequence, 1);
                $dbg = implode("/", $destUrlSequence);
            }
        }

        $newDots = implode('/', $dotsArray);
        $newDest = implode('/', $destUrlSequence);

        $ret = $newDots . $newDest;
        return $ret;
    }

    function mungeRobopageLinks($html)
    {
        //global $sys_static_location;

        $ret = $html;
        $dom = new DomDocument();
        @$dom->loadHTML($html);
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $alink)
        {
            // grep -i actionItem *php
            // removing $_SESSION['prgrmUrlRoot'] is for the special case of the
            // one href that does not have ?robopage= ...which is the "home" link
            // this is a hard-coded robopages hack. Perhaps there is a better way.
            // in the mean time this seems to work
            $href = str_replace($_SESSION['prgrmUrlRoot'], '', $alink->getAttribute('href'));
            $label = $alink->nodeValue;

            if (strstr($href, 'robopage=') || strstr($href, "index.php"))
            {
                $aMungedHref = $this->relativeHref(preg_replace("/^.*=/", '', $href));
                if (strstr($href, "."))
                {
                    $aMungedHref = StaticRoboUtils::stripSuffix($aMungedHref) . ".html";
                }
                $alink->setAttribute("href", $aMungedHref);
                $this->roboLogit($label . ": alink->getAttribute(href): " . $alink->getAttribute("href"));
            }
        }

        $images = $dom->getElementsByTagName('img');
        foreach ($images as $anImg)
        {
            $imgSrc = $anImg->getAttribute('src');

            $aMungedSrc = $this->relativeSrc($imgSrc);
            $anImg->setAttribute("src", $aMungedSrc);
            //$this->roboLogit("anImg->getAttribute(src): " . $anImg->getAttribute("src")) ;
        }


        $ret = $dom->saveHTML();
        return $ret;
    }

    function staticDrone()
    {
        //global $sys_static_mode, $sys_static_location;
        global $sys_static_mode;

        if ((isset($sys_static_mode) && $sys_static_mode == TRUE))
        {
            if (!@stat(getcwd() . '/Pages'))
                @mkdir(getcwd() . '/Pages', 0777);


            if (!@stat(getcwd() . '/Pages/css'))
            {
                @mkdir(getcwd() . '/Pages/css', 0777);
                $this->copyDirContents(getcwd() . '/css', getcwd() . '/Pages/css');
            }

            if (!@stat(getcwd() . '/Pages/js'))
            {
                @mkdir(getcwd() . '/Pages/js', 0777);
                $this->copyDirContents(getcwd() . '/js', getcwd() . '/Pages/js');
            }
            if (!@stat(getcwd() . '/Pages/systemimages'))
            {
                @mkdir(getcwd() . '/Pages/systemimages', 0777);
                $this->copyDirContents(getcwd() . '/systemimages', getcwd() . '/Pages/systemimages');
            }

            for ($i = 0; $i < $this->divcnt; $i++)
            {
                $data = $this->divs[$this->topLevelDivNames[$i]];
                $data = $this->mungeRobopageLinks($data);
                $this->Echostatic($data, "a");
            }
        }
    }

}

?>
