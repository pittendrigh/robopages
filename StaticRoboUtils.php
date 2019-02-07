<?php
include_once("conf/globals.php");

class StaticRoboUtils
{
    static function chmod_r($path, $octal)
    {
        $ret='';
        $dir = new DirectoryIterator($path);
        foreach ($dir as $item)
        {
            $dbg = $item->getPathname();
            if ($dbg[0] == '.')
                continue;
            try
            {
                @chmod($item->getPathname(), $octal);
            }
            catch (Exceptian $a)
            {
                $ret .= $a->getMessage() . "<br/>\n"; 
            }
            if ($item->isDir() && !$item->isDot())
            {
                try
                {
                    StaticRoboUtils::chmod_r($item->getPathname(), $octal);
                }
                catch (Excepton $e)
                {
                    $ret .= $e->getMessage() . "<br/>\n"; 
                }
            }
        }
        return $ret;
    }


    static function fixPageEqualParm($tentativelink)
    {
        // zap any leading slash from the value of  $_GET['robopage']
        $tentativelink = preg_replace(":^\/:", "", $tentativelink);
        // turn any double slashes to a single slash
        $tentativelink = preg_replace(":\/\/[\/]*:", "/", $tentativelink);

        return $tentativelink;
    }

    static function getFilesOptions($dir, $filter = null)
    {
        $farry = StaticRoboUtils::getFilesList($dir, $filter);
        $opstr = '';
        foreach (array_keys($farry) as $akey)
        {
            $opstr .= '<option  value="' . $akey . '">' . $akey . '</option>';
        }
        return $opstr;
    }

    static function getFilesList($dir, $filter = null)
    {
        $files = array();
        $temp = array();

        $fd = opendir($dir);
        if (isset($fd))
        {
            while ($thingy = readdir($fd))
            {
                if ($thingy[0] == "." || $thingy == "LOGS" || $thingy == "archive")
                    continue;

                $candidate = $dir . $thingy;

                if (is_dir($candidate) || strstr($thingy, "obopage"))
                    continue;
                $temp[$thingy] = $candidate;
                //echo $thingy, " thingy<br/>";
            }

            foreach (array_keys($temp) as $afile)
            {
                $apath = $temp[$afile];
                if ($filter == null || $filter != null && preg_match("/" . $filter . "/", $apath))
                {
                    $files[$afile] = $apath;
                    //echo $files[$afile], " == " , $apath, "<br/>";
                }
            }
        }
        closedir($fd);
        return $files;
    }

    static function dbg()
    {
        print "REQUEST_METHOD: ", $_SERVER['REQUEST_METHOD'] . "<br/><br/>";
        foreach (array_keys($_SESSION) as $akey)
        {
            print " $akey s= <b>$_SESSION[$akey] </b><br/>";
        }
        print "<br/>";
        foreach (array_keys($_GET) as $akey)
        {
            print " $akey g= <b>$_GET[$akey]</b><br/>";
        }
        print "<br/>";

        foreach (array_keys($_POST) as $akey)
        {
            print " $akey p= <b>$_POST[$akey]</b><br/>";
        }
    }

    static function checkAuthorityCredentials()
    {
        $ret = TRUE;
        if (!isset($_SESSION['privilege']) || ($_SESSION['privilege'] != 'nimda'))
            $ret = FALSE;
        return $ret;
    }

    static function is_image($file)
    {
        $ret = FALSE;
        $dotpos = strrpos($file, '.');
        if ($dotpos)
        {
            $suffix = substr($file, $dotpos + 1);
            if (stristr($suffix, 'jpg') || stristr($suffix, 'jpeg') || stristr($suffix, 'gif') || stristr($suffix, 'png')
            )
            {
                $ret = TRUE;
            }
        }

        return $ret;
    }

    static function mkLabel($str)
    {
        $suffix = StaticRoboUtils::getSuffix($str);
        $base = StaticRoboUtils::stripSuffix(basename($str));

        $ret = preg_replace(":^.*_:", '', $base);
        $images = array("jpg", "gif", "png");

        if (!in_array($suffix, $images) && $suffix != null)
            $ret .= '.' . $suffix;
        return ($ret);
    }

    static function thumbLabel($file)
    {
        $label = basename($file);
        $tndir = str_replace(".", "", dirname($file)) . "archive/thumbs/";
        $base = "tn-" . basename($file);
        $possiblethumburl = $_SESSION['currentUrlPath'] . $tndir . $base;
        $possiblethumbpath = $_SESSION['currentFilePath'] . $tndir . $base;

        if (file_exists($possiblethumbpath) && is_image($file))
        {
            $label = mk_thumblink($label, $possiblethumburl);
        }
        return $label;
    }

    static function mk_thumblink($label, $imgpath)
    {
        $ret = '<img src="' . $imgpath . '" alt="' . basename($imgpath) . '"/><br/>' . $label;
        return($ret);
    }

    /*
      public static function mkExtraHead() {
      global $sys_defd, $sys_defk;
      $ret = "\n";
      $ret .= '<meta name="description" content="' . $sys_defd . '"/>' . "\n";
      $ret .= '<meta name="keywords" content="' . $sys_defk . '"/>' . "\n";
      return $ret;
      }
     */

    public static function endHTML()
    {
        return "\n" . '</body></html>' . "\n";
    }

    public static function fixPath($path)
    {
        $path = preg_replace(":\.\/:", "", $path);
        $path = preg_replace(":\/\/[\/]*:", "/", $path);
        return ($path);
    }

    public static function cleanName($str)
    {
        global $sys_layout;
        $ret = $str;
        $ret = str_replace("/\s/", "_", $ret);
        $ret = str_replace("/\./", "xxyyzz", $ret);
        $ret = str_replace("/\W/", "", $ret);
        $ret = str_replace("/xxyyzz/", '.', $ret);


        return $ret;
    }

    public static function getSuffix($str)
    {

        $suffix = "";
        $tmp = basename($str);
        $pos = strrpos($str, ".");
        if ($pos != null)
            $suffix = substr($str, $pos + 1);
        return $suffix;
    }

    public static function stripSuffix($str)
    {

        $suffix = $str;
        $pos = strrpos($str, ".");
        if ($pos != null)
            $suffix = substr($str, 0, $pos);

        return $suffix;
    }

    public static function mkImageLabel($str)
    {
        $ret = StaticRoboUtils::stripSuffix($str);
        $ret = preg_replace('/-|_/', ' ', $ret);
        return $ret;
    }

    public static function ouch($str)
    {
        $fp = fopen($_SERVER['DOCUMENT_ROOT'] . "LOGS/error.log", "a");
        $stamp = time();
        $dadate = localtime($stamp);
        $min = $dadate[1];
        $hour = $dadate[2];
        $mday = $dadate[3];
        $month = $dadate[4];
        $year = $dadate[5] + 1900;
        $time = $month . "_" . $mday . "_"
                . $year . "_" . $hour . ":" . $min;
        $msg = $_SERVER['REMOTE_ADDR']
                . ':' . $_SESSION['username']
                . ":" . $time . ":"
                . $str . "\n";
        fwrite($fp, $msg, 80);
        fclose($fp);
        exit;
    }

    public static function getpostClean()
    {
        global $sys_layout;
        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            if (isset($_GET['page']))
            {

                // strip off any end of URL directory slash--to keep the permutations manageable
                $_GET['page'] = preg_replace(':/$:', '', $_GET['page']);
                if (substr($_GET['page'], 0, 1) == '/')
                    $_GET['page'][0] = '';
                if (substr($_GET['page'], 0, 1) == '.')
                    StaticRoboUtils::ouch("get dots");
                else if (strstr($_GET['page'], '..'))
                    StaticRoboUtils::ouch("embeded get dots");
                else if (substr($_GET['page'], 0, 5) == 'nimda')
                    unset($_GET['page']);
            }
        }
        else if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if (isset($_POST['page']))
            {

                if (substr($_GET['page'], 0, 1) == '/')
                    $_GET['page'][0] = '';

                if (substr($_POST['page'], 0, 1) == '/')
                    StaticRoboUtils::ouch("post slash");
                else if (substr($_POST['page'], 0, 1) == '.')
                    StaticRoboUtils::ouch("post dots");
                else if (strstr($_POST['page'], '..') == '.')
                    StaticRoboUtils::ouch("embeded post dots");
                else if (substr($_POST['page'], 0, 5) == 'nimda')
                    unset($_POST['page']);
            }
        }

        if (isset($_GET['layout']))
        {
            if (!@stat(getcwd() . '/layouts/' . $_GET['layout'] . '.xml') && isset($sys_layout))
                $_GET['layout'] = $sys_layout;
        }
    }
}
?>
