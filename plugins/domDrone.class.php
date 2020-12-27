<?php
@session_start();

error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once("conf/globals.php");
require_once("StaticRoboUtils.php");
require_once("plugins/roboMimeTyper.php");
include_once("plugins/pluginnotfound.php");

class domDrone
{
    public $mimer;
    protected $definitionFile;
    protected $topLeveDivNames;
    protected $divs;
    protected $config = array();
    protected $cssfiles; // array, this has to come from the layout.xml
    protected $jsfiles; // array, this has to come from the layout.xml
    protected $dbg = 0;
    protected $layoutXML;
    protected $dirlayouts;

    function __construct()
    {
        $this->mimer = new roboMimeTyper();
        $this->cssfiles = null;
        $this->jsfiles = null;
        $this->init();
        $this->readDefinitionFile();
    }

    function init()
    {
        global $sys_layout;

        StaticRoboUtils::getpostClean();

        if (isset($_GET['dbg']))
            $this->dbg = 1;

        $_SESSION['prgrmDocRoot'] = getcwd() . '/fragments/';
        $_SESSION['prgrmUrlRoot'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', getcwd() . '/');

        $this->setPathAndUrlParms();
        $this->determineLayout();
        $this->determineTitle();
        if ($this->dbg)
            $this->dbg();
    }

    function determineLayout()
    {
        global $sys_layout;


        $this->definitionFile = 'layouts/robo.xml';
        if (isset($sys_layout) && $sys_layout != null)
            $this->definitionFile = 'layouts/' . $sys_layout . '.xml';

        // perhaps a relevant conf/dirlayouts.ini entry exists
        // this layout override method is useful for all files in a directory
        if (isset($_GET['robopage']))
        {
            $dirlayoutLines = file("conf/dirlayouts.ini");
            $dlcnt = count($dirlayoutLines);
            /*
              Fly-Tying/Sandy-Pittendrigh/BestOf|gallery
              Birds|gallery
             */
            for ($i = 0; $i < $dlcnt; $i++)
            {
                if (trim($dirlayoutLines[$i]) == '')
                    continue;
                $tmp = explode("|", trim($dirlayoutLines[$i]));
                // make a hash for subsequent comparison
                $this->dirlayouts[$tmp[0]] = $tmp[1];
            }

            $test = $_SESSION['prgrmDocRoot'] . $_GET['robopage'];
/*
           ///no dirlayouts apply at the directory level only, not on individual leaf-level files
            if (!is_dir($test))
                $test = dirname($test);
*/

         if(isset($this->dirlayouts))
            foreach (array_keys($this->dirlayouts) as $akey)
            {
                if (strstr($test, $akey))
                {
                    $this->definitionFile = 'layouts/' . $this->dirlayouts[$akey] . '.xml';
                    //echo $this->definitionFile. "<br/>";
                }
            }
        }

        // perhaps a current directory wide override exists, as  '/roboresources/layout'
        // ...this could conceivably override conf/layouts.ini
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/layout'))
        {
            //echo "b<br/>";
            $this->definitionFile = 'layouts/' . trim(@file_get_contents($_SESSION['currentDirPath'] . 'roboresources/layout')) . '.xml';
        }

        // perhaps a page-specific override exists, which takes precedence even over diredctory wide
        // ...this would also override conf/layouts.ini
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/' . $_GET['robopage'] . '-layout'))
        {
            //echo "c<br/>";
            $this->definitionFile = 'layouts/' . trim(@file_get_contents($_SESSION['currentDirPath']
                                    . 'roboresources/' . $_GET['robopage'] . '-layout')) . '.xml';
        }

        // perhaps $_GET['layout'] exists, which takes precedence over all the above
        if (isset($_GET['layout']) && file_exists("layouts/" . $_GET['layout'] . '.xml'))
        {
            //echo "d<br/>";
            $this->definitionFile = 'layouts/' . $_GET['layout'] . '.xml';
        }

        $_SESSION['layout'] = StaticRoboUtils::stripSuffix(basename($this->definitionFile));
    }

    function determineTitle()
    {
        global $sys_title;
        $title = $sys_title;

        // now perhaps override with locally defined page level title
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/title-' . basename($_SESSION['currentDisplay'])))
        {
            $overridefile = $_SESSION['currentDirPath'] . 'roboresources/title-' . $_SESSION['currentDisplay'];
            $title = @file_get_contents($overridefile);
        }
        // or perhaps override with locally defined direcdtory level title
        else if (@stat($_SESSION['currentDirPath'] . 'roboresources/title'))
        {
            $overridefile = $_SESSION['currentDirPath'] . 'roboresources/' . $_SESSION['currentDisplay'];
            $title = @file_get_contents($overridefile);
        }
        else // else try to use $_SESSION['currentDisplay'] or directory of $_SESSION['currentDisplay']
        {
            if (isset($_SESSION['currentDisplay']) && $_SESSION['currentDisplay'] != '' && !strstr($_SESSION['currentDisplay'], 'index'))
            {
                $title = $_SESSION['currentDisplay'];
            }
            else
            {

                $testdir = dirname($_SESSION['currentDirUrl']);
                if ($testdir != '' && $testdir != '.')
                {
                    $title = $testdir;
                }
            }
        }
        $_SESSION['title'] = StaticRoboUtils::stripSuffix($title);
    }

    function processXMLCSSLines()
    {
        foreach ($this->layoutXML->xpath("/layout/cssfiles/file") as $anode)
        {
            if ($this->cssfiles == null)
                $this->cssfiles = array();
            $this->cssfiles[] = $anode;
        }
        foreach ($this->layoutXML->xpath("/layout/cssfiles/link") as $anode)
        {
            if ($this->cssfiles == null)
                $this->cssfiles = array();
            $this->cssfiles[] = $anode;
        }
    }

    function processXMLJSLines()
    {
        foreach ($this->layoutXML->xpath("/layout/jsfiles/file") as $anode)
        {
            if ($this->jsfiles == null)
                $this->jsfiles = array();
            $this->jsfiles[] = $anode;
        }
    }

    function processXMLPluginLines()
    {
        foreach ($this->layoutXML->xpath("/layout/plugins/file") as $anode)
        {
            /*
            if ($this->plugins == null)
                $this->plugins = array();
            $this->plugins[] = $anode;
            */
            include_once("$anode");
        }
    }

    // will we ever need sub-elementName and not assume  div for subPlugins?
    function stitchContent($plugin, $pluginNameList, $divid)
    {
        $ret = '';

        if (strstr($pluginNameList, ","))
        {
            $pluginNames = explode(",", $pluginNameList);
            $cnt = count($pluginNames) - 1;

            for ($i = 0; $i < $cnt; $i++)
            {
                $tmpStr = $pluginNames[$i];
                $divid = $subPluginName = $pluginNames[$i];

                if (strstr($tmpStr, ":"))
                {
                    // googleAd:mkGoogle, for instance, where googleAd is an id and mkGoogle is the plugin
                    $subPieces = explode(":", $tmpStr);
                    $divid = $subPieces[0];
                    $subPluginName = $subPieces[1];
                }

                $subPlugin = new $subPluginName();
                $ret .= $subPlugin->getOutput($divid);
            }
        }

        if (isset($plugin) && $plugin != null)
            $ret .= $plugin->getOutput($divid);

        return $ret;
    }

    function getPluginName($str)
    {
        $ret = '';

        $ret .= $str; // default value

        /* $str comes from XML attribute src="string"
          Might be src="file" (indicating the HTML source would come from plugins/file.php)
          Might also be src="banner:file" which has more meaning in other contexts than this one
          banner:file means id="banner" src="file" ... However, the only time getPluginName($str) is called
          we already know the block element name id="whatever" so we can parse off "file" portion here and forget banner
          Might also be a long string of comma delimited names such as:
          src="mkBreadcdrumbs,mkGoogle,textblurb:file,flexyFileContent"
          so each comma delimeted substring must deal with optional colons
          in this case flexyFileContent would be the relevant plugin name.  All previous sources become additional content
          generated for this block element, generated before the flexyFileContent content
          Comma delimeted lists of content are useful for liquid layouts.
         */

        // if not a comma delimited list it still MIGHT be colon delimeted id:plugin pair
        if (!strstr($str, ","))
        {
            if (strstr($str, ":"))
            {
                $pieces = explode(":", $str);
                $ret = $pieces[1];
            }   // this else condition is already set as a default above
        }
        else
        {

            // else it MUST now be a comma delimited list
            $pluginNames = explode(",", $str);
            $cnt = count($pluginNames) - 1;

            $ret = $lastItem = $pluginNames[$cnt];

            // last item MIGHT be a colon delimited id:plugin pair
            if (strstr($lastItem, ":"))
            {
                // bannerad:file perhaps, where file indicates plugins/file.php
                // and where bannerad is a block element ID named in the layout XML
                $subPieces = explode(":", $lastItem);
                //$divid = $subPieces[0];
                $ret = $subPieces[1];
            }
        }

        return $ret;
    }

    function doBlocksXML($simpleXMLBlockElement)
    {
        $ret = '';

        // elementName might be header footer div section etc
        $elementName = $simpleXMLBlockElement->getName();

        // divid is the id= for this block element (which may not be therA)
        $divid = trim($simpleXMLBlockElement[@id]);

        // optional klass= in the xml
        $divklass = trim($simpleXMLBlockElement[@klass]);
        // file is the default plugin
        $divsrc = $pluginNameList = 'file';

        if (isset($simpleXMLBlockElement[@src]))
        {
            $divsrc = $this->getPluginName($simpleXMLBlockElement[@src]);
            $pluginNameList = $simpleXMLBlockElement[@src];
        }
        else
        {
            $divsrc = $pluginNameList = "container";
        }

        // now override the default divsrc if in $_GET
        if (isset($_GET[$divid]))
        {
            $divsrc = $pluginNameList = $_GET[$divid];
        }

        $divattrLbl = 'id';

        // if there is no id there might be a klass
        if ($divid == null && isset($divklass))
        {
            $divid = $divklass;
            $divattrLbl = 'klass';
        }

        //echo "divid: ", $divid, " divsrc: [", $divsrc, "]<br/>";

        switch ($divsrc)
        {
            case 'wrapper':
                $ret .= "\n" . '<' . $elementName . ' id="wrapper">';
                break;
            case '':
                $ret .= "\n" . '<' . $elementName . '>';
                break;
            case 'container':
                // class is a keyword so it is indicated here as the optional attribute 'klass'
                // klass means we want to identify this block element as a class="xyz" instead of id="abc"
                if ($divattrLbl == 'klass')
                    $ret .= "\n" . '<' . $elementName . ' class="' . $divid . '">';
                else
                    $ret .= "\n" . '<' . $elementName . ' id="' . $divid . '">';
                break;
            default:
                if ($divattrLbl == 'klass')
                    $ret .= "\n" . '<' . $elementName . ' class="' . $divid . '">';
                else
                {
                    // else there might be klass="xxx" and id="abc"
                    $ret .= "\n" . '<' . $elementName;
                    if (isset($divklass) && $divklass != null)
                        $ret .= ' class="' . $divklass . '" ';
                    $ret .= ' id="' . $divid . '">';
                }

                /*
                  if (!@stat(getcwd() . '/plugins/' . $divsrc . '.php')) {
                  $divid = $divsrc;
                  $divsrc = 'pluginnotfound';
                  }
                 */
                //echo $divsrc, "<br/>";
                $plugin = new $divsrc();
                break;
        }
        foreach ($simpleXMLBlockElement->children() as $simpleXMLBlockElementchild)
        {
            $ret .= $this->doBlocksXML($simpleXMLBlockElementchild);
        }

        if (isset($plugin))
            $ret .= $this->stitchContent($plugin, $pluginNameList, $divid);
        $ret .= '</' . $elementName . '>';
        return $ret;
    }

    function readDefinitionFile()
    {
        /*
          if (isset($_SESSION['privilege']) && $_SESSION['privilege'] == 'nimda')
          {
          $_SESSION['layout'] = 'nerd';
          $this->definitionFile = 'layouts/' . $_SESSION['layout'] . '.xml';
          }
         */
        $this->divs = array();
        $this->topLevelDivNames = array();
        $dfilepath = getcwd() . '/' . $this->definitionFile;
        $stuff = file_get_contents($dfilepath);
        $this->layoutXML = simplexml_load_file($this->definitionFile);
        $this->processXMLCSSLines();
        $this->processXMLJSLines();
        $this->processXMLPluginLines();
        foreach ($this->layoutXML->xpath("/layout/bodycontent/div") as $simpleXMLBlockElement)
        {
            $divid = trim($simpleXMLBlockElement[@id]);
            $this->topLevelDivNames[] = $divid;
            $this->divs[$divid] = $this->doBlocksXML($simpleXMLBlockElement);
        }
        $this->divcnt = count($this->topLevelDivNames);
    }

    function getDefaultDisplay()
    {
        $ret = '';

        // getDefaultDisplay() sometimes called with a not null $_GET['robopage'] == is_dir()
        if (isset($_GET['robopage']))
            $dir = $_SESSION['prgrmDocRoot'] . $_GET['robopage'];
        else
            $dir = $_SESSION['prgrmDocRoot'];

        $dh = opendir($dir);
        while ($dh != null && (FALSE != ($file = readdir($dh))))
        {
            if (is_dir($dir . '/' . $file) || $file[0] == '.' || $file == 'roboresources' || $file == 'admin' || $file == 'LOGS')
            {
                continue;
            }
            if ($this->mimer == null)
            {
                $this->mimer = new roboMimeTyper();
            }
            $linkType = $this->mimer->getRoboMimeType($dir . '/' . $file);
            $tarr = array('fragment', 'image', 'text', 'iframe', 'pdf');
            if (in_array($linkType, $tarr))
            {
                //echo "getDefaultDislay: ", $file, "<br/>";
                $ret = $file;
                if (strstr($file, "index"))
                    break;
            }
        }
        if ($dh != null)
            closedir($dh);

        return $ret;
    }

    function setPathAndUrlParms()
    {
        if (isset($_GET['robopage']) && $_GET['robopage'] == '/')
            $_GET['robopage'] = '';

        // if no usable $_GET['robopage']
        if (!isset($_GET['robopage']) || $_GET['robopage'] == '')
        {
            $_SESSION['currentDisplay'] = $this->getDefaultDisplay();
            $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'];
            $_SESSION['currentDirUrl'] = '';
            $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/';
        }
        else // else there is a usable $_GET['robopage'] which may point to a directory
        {
            $test_is_dirpath = StaticRoboUtils::fixPath($_SESSION['prgrmDocRoot'] . $_GET['robopage']);

            if (@is_dir($test_is_dirpath))
            {
                $_SESSION['currentDisplay'] = $this->getDefaultDisplay();
                $_SESSION['currentDirPath'] = substr($test_is_dirpath, -1) == '/' ? $test_is_dirpath : $test_is_dirpath . '/';
                $_SESSION['currentDirUrl'] = '/' . substr($_GET['robopage'], -1) == '/' ? $_GET['robopage'] : $_GET['robopage'] . '/';
                $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/' . $_GET['robopage'] . '/';
            }
            else
            {   // is a $_GET['robopage'] that points to a leaf level file
                // which may or may not be in the document root
                if(!strstr($_GET['robopage'],'/'))
                    $prefixDir = '';
                else
                    $prefixDir = dirname($_GET['robopage']). '/';
                $_SESSION['currentDisplay'] = basename($_GET['robopage']);
                $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'] . $prefixDir;
                $_SESSION['currentDirUrl'] = $prefixDir;
                $_SESSION['currentClickDirUrl'] = dirname($_SESSION['prgrmUrlRoot'] . 'fragments/' . $_GET['robopage']) . '/';
            }
        }

        $_SESSION['currentDisplay'] = preg_replace(":^\/:", '', $_SESSION['currentDisplay']);
        $_SESSION['currentDirPath'] = StaticRoboUtils::fixPath($_SESSION['currentDirPath']);
        $_SESSION['currentDirUrl'] = StaticRoboUtils::fixPath($_SESSION['currentDirUrl']);
        $_SESSION['currentDirUrl'] = preg_replace(":^\/:", '', $_SESSION['currentDirUrl']);
        $_SESSION['currentClickDirUrl'] = StaticRoboUtils::fixPath($_SESSION['currentClickDirUrl']);
        //$_SESSION['currentClickDirUrl'] = preg_replace(":^\/:", '', $_SESSION['currentClickDirUrl']);
    }

    function printDivs()
    {
        $ret = '';
        for ($i = 0; $i < $this->divcnt; $i++)
        {
            $ret .= $this->divs[$this->topLevelDivNames[$i]];
        }
        return $ret;
    }

    function createCSSLinks($static_mode)
    {
        $ret = '';
        $cssjsPathHack = str_replace($_SESSION['prgrmDocRoot'], '', $_SESSION['currentDirPath']);
        $slashCount = substr_count($cssjsPathHack, '/');

        foreach ($this->cssfiles as $cssfile)
        {
            $ret .= "\n" . '<link rel="stylesheet" type="text/css" href="';

            if (isset($static_mode) && $static_mode)
            {
                for ($j = 0; $j < $slashCount; $j++)
                    $ret .= '../';
            }
            $ret .= $cssfile . '"/>';
        }

        return ($ret);
    }

    function createJSLinks($static_mode)
    {
        $ret = '';
        $cssjsPathHack = str_replace($_SESSION['prgrmDocRoot'], '', $_SESSION['currentDirPath']);
        $slashCount = substr_count($cssjsPathHack, '/');
        if (isset($this->jsfiles) && $this->jsfiles != null)
        {
            foreach ($this->jsfiles as $jsfile)
            {
                $ret .= "\n" . '<script src="';
                if (isset($static_mode) && $static_mode)
                {
                    for ($j = 0; $j < $slashCount; $j++)
                        $ret .= '../';
                }

                $ret .= "$jsfile" . '"></script>';
            }
        }

        return ($ret);
    }


    function determineOgUrl()
    {
      global $sys_ogurl;
      $ret = $sys_ogurl;

      if(isset($_GET['robopage']))
      {
          $ret = "https://montana-riverboats.com?robopage=" . $_GET['robopage'];
      }
      return ($ret);
    }

    function determineOgImage()
    {
      global $sys_ogimage;
      $ret = $sys_ogimage;

      if(isset($_GET['robopage']) && strstr($_GET['robopage'],"jpg"))
      {
         $ret = "/fragments/" . $_GET['robopage'];
  
      }
      return ($ret);
    }

    function startHTML($sstatic_mode)
    {
        global $sys_nofollow, $sys_ogimage, $sys_ogurl;

        $static_mode = $sstatic_mode;

        $ogimage = $this->determineOgImage();
        $ogurl = $this->determineOgUrl();

        //<META name="verify-admitad" content="xxxx" />

        $title = $_SESSION['title'];
        $ret = '';
        $ret .= <<<ENDO
<!DOCTYPE html>
<html lang="en">
  <head>
  <META http-equiv="Content-Type" content="text/html;charset=utf-8"/>
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
  <META name="viewport" content="width=device-width, initial-scale=1.0"/>
  <META property="og:type"   content="website" />
  <META property="og:title"   content="$title" />
  <META property="og:url"   content="$ogurl" />
  <META	property="og:image" content="$ogimage" />
  <META name="google-site-verification" content="xxxyyyzzz" />
ENDO;

        $ret .= "\n" . '<title>' . $title . '</title>';
        $ret .= "\n";

        // set conf/globals.php $sys_nofollow=TRUE for test subdomains

        if (isset($sys_nofollow) && $sys_nofollow == TRUE)
            $ret .= '<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">';

        $ret .= $this->mkExtraHead();
        $ret .= $this->createCSSLinks($static_mode);
        $ret .= $this->createJSLinks($static_mode);
        $ret .= "\n" . '<link rel="shortcut icon" href="/favicon.ico" sizes="16x16 32x32" type="image/x-icon" />';
        $ret .= "\n" . '</head> ' . "\n\n" . '<body>' . "\n";
/*
        $ret .= <<<ENDO
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = 'https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v3.0';
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<script src="https://apis.google.com/js/platform.js" async defer></script>
ENDO;
*/

        return $ret;
    }

    function getCurrentDirFilenames($keywords)
    {
        $dir = $_SESSION['currentDirPath'];

        if ($dir_handle = opendir($dir))
        {
            while (($file = readdir($dir_handle)) !== false)
            {
                if ($file != '.' && $file != '..' && !strstr($file, 'index') && !strstr($file, 'roboresources'))
                {
                    $keyword = preg_replace(":_|-:", ' ', StaticRoboUtils::stripSuffix($file));
                    $keywords .= ',' . $keyword;
                }
            }
            closedir($dir_handle);
        }
        return $keywords;
    }


    function string2WordList($str, $delimeter)
    {
            $ret = '';
            $pathChunks = explode('/', $str);
            foreach ($pathChunks as $aChunk)
            {
                   $ret .=  $delimeter . StaticRoboUtils::stripSuffix(trim($aChunk));
            }

         $ret = preg_replace("/_|-/",$delimeter, $ret);
         return $ret;
    }

    function mkExtraHead()
    {
        global $sys_defd, $sys_defk;

        $ret = $keyswords = $metadesc = $metakeys = '';

        $metadesc = ''; 

        if (!isset($_GET['robopage']))
            $metadesc = $sys_defd;

        if(isset($_GET['robopage']) && $_GET['robopage'] != null)
        {
           $metadesc = $this->string2WordList($_GET['robopage'],' ');
        }
        // We have a default metadesc. Now override it if 'roboresources/metadesc' exists 
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/metadesc'))
        {
            $metadesc = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metadesc');
            //`$metadesc = $this->string2WordList(StaticRoboUtils::stripSuffix($_SESSION['currentDisplay']),' ') . ' ' . $metadesc;
        }

        // now override again if a page-specific metadesc exists 
        $currentPageTest = isset($_GET['robopage']) ? basename($_GET['robopage']) : 'xxx';
        $metadescPerPage = $_SESSION['currentDirPath'] . 'roboresources/' . $currentPageTest  . '_metadesc';
        if (@stat($metadescPerPage))
        {
            $metadesc = file_get_contents($metadescPerPage);
        }

        // similar with keys ... make a default.  Toss it if something better exists
        $metakeys = ''; 

        if (!isset($_GET['robopage']))
            $metakeys = $sys_defk;

        if(isset($_GET['robopage']) && $_GET['robopage'] != null)
        {
           $metakeys = $this->string2WordList($_GET['robopage'],',');
           //$metakeys = $this->string2WordList(StaticRoboUtils::stripSuffix($_SESSION['currentDisplay']),',') . ',' . $metakeys;
        }
        if(substr($metakeys,0,1) == ',')
          $metakeys = substr($metakeys,1);
           

        // A hand-edited roboresources/metakeys file applies to all pages in this directory.
        // If exists then discard all the above and override
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/metakeys'))
        {
            $metakeys = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metakeys');
        }

        // now check for a page-specific override for all of the above...for this page
        if (@stat($_SESSION['currentDirPath'] . 'roboresources/' . $currentPageTest  . '_metakeys'))
        {
            $metakeys = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/' . $currentPageTest   . '_metakeys');
        }

        $metadesc = trim($metadesc);
        $metakeys = str_replace(",,",",",$metakeys);

        $ret .= "\n" . '<META name="description" content="' . $metadesc . '"/>' . "\n";
        $ret .= '<META name="keywords" content="' . $metakeys . '"/>' . "\n";

        return $ret;
    }

    function dbg()
    {
        print "  definitionFile = <b>" . $this->definitionFile . "</b><br/><br/>";

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

}
