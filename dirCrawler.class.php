<?php

@session_start();

error_reporting(E_ALL);
ini_set("display_errors", 1);

include_once("conf/globals.php");
require_once("staticRoboUtils.php");
require_once("plugins/roboMimeTyper.php");

$plugins = file("conf/plugins.ini");
$pcnt = count($plugins);
for ($i = 0; $i < $pcnt; $i++) {
    $plugin = trim($plugins[$i]) . '.php';
    include_once("plugins/$plugin");
}
include_once("plugins/pluginnotfound.php");

class dirCrawler {

    public $mimer;
    protected $definitionFile;
    protected $topLeveDivNames;
    protected $divs;
    protected $config = array();
    protected $dirlayouts = array();
    protected $cssfiles; // array, this has to come from the layout.xml
    protected $jsfiles; // array, this has to come from the layout.xml
    protected $dbg = 0;
    protected $layoutXML;

    function __construct() {
        $this->mimer = new roboMimeTyper();
        $this->cssfiles = null;
        $this->jsfiles = null;
        $this->init();
        $this->readDefinitionFile();
    }

    function dbg() {
        print "definitionFile: " . $this->definitionFile . "<br/>";

        while (list($k, $v) = each($_SESSION)) {
            print "$k s= $v<br/>";
        }
        print "<br/><br/>";
        while (list($k, $v) = each($_GET)) {
            print "$k gg= $v<br/>";
        }
        print "<br/><br/>";
        while (list($k, $v) = each($_POST)) {
            print "$k p= $v<br/>";
        }
    }

    function determineLayout() {
        global $sys_layout;
        $this->definitionFile = 'layouts/robo.xml';

        if (isset($_GET['layout'])) {
            $this->definitionfile = 'layouts/' . $_GET['layout'] . '.xml';
        } 
        //else if (isset($sys_layout)) { $this->definitionfile = 'layouts/' . $sys_layout . '.xml'; }

        // first if below and first else could conflict.  Tough. We use the if
        if (isset($_GET['layout']) && file_exists("layouts/" . $_GET['layout'] . '.xml')) {
            $this->definitionFile = 'layouts/' . $_GET['layout'] . '.xml';
        }
        else if (@stat($_SESSION['currentDirPath'].'/layout')){
             $line = file($_SESSION['currentDirPath'].'/layout');
             $this->definitionFile = 'layouts/' . trim($line[0]) . '.xml';
        }
        else if (isset($_GET['page'])) {
            $dirlayoutLines = file("conf/dirlayouts.ini");
            $dlcnt = count($dirlayoutLines);
            //  page=Driftboats/honky-dory/honkey-dory-online-plans
            /* .....we want everything from Fly-Tying down?
              Fly-Tying/Sandy-Pittendrigh/BestOf|roboBucket
              Bugs|roboBucket
              Birds|roboBucket
             */
            for ($i = 0; $i < $dlcnt; $i++) {
                $tmp = explode("|", trim($dirlayoutLines[$i]));
                // path in dirlayouts.ini = layout
                $this->dirlayouts[$tmp[0]] = $tmp[1];
            }

            $test = $_GET['page'];
            if (strstr($_GET['page'], '\.'))
                $test = dirname($_GET['page']);

            foreach (array_keys($this->dirlayouts) as $akey) {
                if (strstr($test, $akey)) {
                    $this->definitionFile = 'layouts/' . $this->dirlayouts[$akey] . '.xml';
                }
            }
        }

       // echo 'end of dirCrawler determineLayout: <b style="color: green;">', $this->definitionFile, "</b><br/>";
    }

    function init() {
        global $sys_layout;

        staticRoboUtils::getpostClean();
        $this->determineLayout();
        if (isset($_GET['dbg']))
            $this->dbg = 1;

        // What if we jump to another robopages installation on the same server?
        // prgrmDocRoot will be set but wrong. So we cannot use the following if
        // unless changes are made. Hmmm.  Perhaps we only need to set prgrmUrlRoot from config file
        // and then all others on the fly. Or perhaps to set prgrmUrlRoot dynamically 
        // if and only if it's not in a config file 
        //
        // In any case,  for now we have to read it every time, no matter what
        // if (!isset($_SESSION['prgrmUrlRoot']) && stat("conf/dirLStart.conf.ini")) {

        if (1 > 0) {
            $lines = file("conf/dirLStart.conf.ini");
            foreach ($lines as $aline) {
                $tokens = explode("=", $aline);
                $label = trim($tokens[0]);
                $value = trim($tokens[1]);
                $_SESSION[$label] = $value;
            }
        }

        $this->setPathAndUrlParms();

        // In any given directory we can override the default layout for *any* <block element>.
        // if /dir/dir/dir/archive/footer.frag exists we use that footer instead of the global /fragments/archive/footer.frag
        // The above happens in plugins/file.php 
        // Here if /dir/dir/archive/layout is a -type f file whose contents is "gallery"
        // then the global default layout will be replaced by /layouts/gallery.xml

        $testpath = $_SESSION['currentDirPath'] . 'archive/layout';
        if (@stat($testpath)) {
            $this->definitionFile = 'layouts/' . trim(file_get_contents($testpath)) . '.xml';
        }
    }

    function processXMLCSSLines() {
        foreach ($this->layoutXML->xpath("/layout/cssfiles/file") as $anode) {
            if ($this->cssfiles == null)
                $this->cssfiles = array();
            $this->cssfiles[] = $anode;
        }
        foreach ($this->layoutXML->xpath("/layout/cssfiles/link") as $anode) {
            if ($this->cssfiles == null)
                $this->cssfiles = array();
            $this->cssfiles[] = $anode;
        }
    }

    function processXMLJSLines() {
        foreach ($this->layoutXML->xpath("/layout/jsfiles/file") as $anode) {
            if ($this->jsfiles == null)
                $this->jsfiles = array();
            $this->jsfiles[] = $anode;
        }
        foreach ($this->layoutXML->xpath("/layout/jsfiles/link") as $anode) {
            if ($this->jsfiles == null)
                $this->jsfiles = array();
            $this->jsfiles[] = $anode;
        }
    }

    // will we ever need sub-elementName and not assume  div for subPlugins?
    function assembleContent($plugin, $divsrcs, $divid) {
        $ret = '';

        if (strstr($divsrcs, ",")) {
            $pluginNames = explode(",", $divsrcs);
            $cnt = count($pluginNames) - 1;

            for ($i = 0; $i < $cnt; $i++) {
                $tmpStr = $pluginNames[$i];
                $divid = $subPluginName = $pluginNames[$i];

                if (strstr($tmpStr, ":")) {
                    // googleAd:mkGoogle, for instance
                    $subPieces = explode(":", $tmpStr);
                    $divid = $subPieces[0];
                    $subPluginName = $subPieces[1];
                    //echo "divid: ", $divid, " subPluginName: ", $subPluginName, "<br/>";
                }

                $ret .= '<div id="' . $divid . '">';

                $subPlugin = new $subPluginName();
                $ret .= $subPlugin->getOutput($divid);
                $ret .= '</div>';
            }
        }


        $ret .= $plugin->getOutput($divid);

        return $ret;
    }

    function getPluginName($str) {
        $ret = '';

        $ret .= $str; // default value
        // echo "str: ", $str, "<br/>";

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
        if (!strstr($str, ",")) {
            if (strstr($str, ":")) {
                $pieces = explode(":", $str);
                $ret = $pieces[1];
            }   // this else condition is already set as a default above
        } else {

            // else it MUST now be a comma delimited list
            $pluginNames = explode(",", $str);
            $cnt = count($pluginNames) - 1;

            $ret = $lastItem = $pluginNames[$cnt];

            // last item MIGHT be a colon delimited id:plugin pair
            if (strstr($lastItem, ":")) {
                // bannerad:file perhaps, where file indicates plugins/file.php  
                // and where bannerad is a block element ID named in the layout XML
                $subPieces = explode(":", $lastItem);
                //$divid = $subPieces[0];
                $ret = $subPieces[1];
                //echo "divid: ", $divid, " pluginName: ", $ret, "<br/>";
            }
        }

        return $ret;
    }

    function doBlocksXML($adiv) {
        $ret = '';

        // elementName might be header footer div section etc
        $elementName = $adiv->getName();

        // divid is id= which may not be there
        $divid = trim($adiv[@id]);

        // optional klass= in the xml
        $divklass = trim($adiv[@klass]);
        $divsrc = $divsrcs = 'file';

        //echo $divid, " dirCrawler doBlocksXML definitionFile: <b>", $this->definitionFile, "</b><br/>";
        if (isset($adiv[@src])) {
            $divsrc = $this->getPluginName(trim($adiv[@src]));
            $divsrcs = trim($adiv[@src]);
        } else {
            $divsrc = $divsrcs = "container";
        }

        // now override the default divsrc if in $_GET
        if (isset($_GET[$divid])) {
            $divsrc = $divsrcs = $_GET[$divid];
        }

        $divattrLbl = 'id';

        // if there is no id there might be a klass
        if ($divid == null && isset($divklass)) {
            $divid = $divklass;
            $divattrLbl = 'klass';
        }


        //echo "divid: ", $divid, " divsrc: [", $divsrc, "]<br/>";

        switch ($divsrc) {
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
                    // else there might be klass="xxx" and id="yyy"

                    $ret .= "\n" . '<' . $elementName;
                    if(isset($divklass) && $divklass != null)
                      $ret .= ' class="' . $divklass . '" ';
                    $ret .= ' id="' . $divid . '">';
                }

                /*
                  if (!@stat(getcwd() . '/plugins/' . $divsrc . '.php')) {
                  $divid = $divsrc;
                  $divsrc = 'pluginnotfound';
                  }
                 */
                $plugin = new $divsrc();
                //echo $divid, " on ", $divsrc, "<br/>";
                $dbg = $this->assembleContent($plugin, $divsrcs, $divid);
                $ret .= $dbg;
                break;
        }
        foreach ($adiv->children() as $adivchild) {
            $ret .= $this->doBlocksXML($adivchild);
        }
        $ret .= '</' . $elementName . '>';
        return $ret;
    }

    function readDefinitionFile() {
        $this->divs = array();
        $this->topLevelDivNames = array();
        $this->layoutXML = simplexml_load_file($this->definitionFile);
        $this->processXMLCSSLines();
        $this->processXMLJSLines();
        foreach ($this->layoutXML->xpath("/layout/bodycontent/div") as $adiv) {
            $divid = trim($adiv[@id]);
            $this->topLevelDivNames[] = $divid;
            $this->divs[$divid] = $this->doBlocksXML($adiv);
        }
        $this->divcnt = count($this->topLevelDivNames);
    }

    function getDefaultDisplay() {
        $ret = '';
        if (isset($_GET['page']))
            $dir = $_SESSION['prgrmDocRoot'] . $_GET['page'];
        else
            $dir = $_SESSION['prgrmDocRoot'];
        $dh = opendir($dir);
        while ($dh != null && (FALSE != ($file = readdir($dh)))) {
            if ($file == '.' || $file == 'archive' || $file == 'admin' || $file == 'LOGS') {
                continue;
            }
            $linkType = $this->mimer->getRoboMimeType($dir . $file);
            $tarr = array('fragment', 'image', 'text');
            if (in_array($linkType, $tarr)) {
                $ret = $file;
                if (strstr($file, "index"))
                    break;
            }
        }
        if ($dh != null)
            closedir($dh);

        return $ret;
    }

    function setPathAndUrlParms() {

        if (!isset($_GET['page'])) {
            // roboAdmin started from a bookmark will need a special case here, where we
            // don't lose the  current $_SESSION['currentDirPath'] and Url ...........
            // ...so, do we override the whole setPathAndUrlParms for roboAdmin?
            // or make this if block a function call?
            //echo "a<br/>";
            $_SESSION['currentDisplay'] = '/' . $this->getDefaultDisplay();
            $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'];
            $_SESSION['currentDirUrl'] = '';
            $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/';
        } else {
            // there is a $_GET['page'], which might point to a directory
            //echo "ab<br/>";
            $pget = isset($_GET['page']) ? $_GET['page'] : '';
            if (@is_dir($_SESSION['prgrmDocRoot'] . $pget))
                $pget .= '/';

            $test_is_dirpath = $_SESSION['prgrmDocRoot'] . $pget;
            //echo "test_is_dirpath: ", $test_is_dirpath, "<br/>";
            if (@is_dir($test_is_dirpath)) {
                //echo "abc<br/>";
                $_SESSION['currentDisplay'] = '/' . $this->getDefaultDisplay();
                $_SESSION['currentDirPath'] = $test_is_dirpath;
                $_SESSION['currentDirUrl'] = $pget;
                $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/' . $pget;
                // if($pget[strlen($pget)-1] != '/')
                //    $pget .= '/';
            } else { // is a _GET['page'] that points to a leaf level file
                //echo "abcd<br/>";
                $test_dirname = dirname($_GET['page']) == '.' ? '' : dirname($pget) . '/';
                $test_dirname = $test_dirname == '/' ? '' : $test_dirname;
                $_SESSION['currentDisplay'] = '/' . basename($_GET['page']);
                $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'] . $test_dirname;
                $_SESSION['currentDirUrl'] = $test_dirname;
                $_SESSION['currentClickDirUrl'] = dirname($_SESSION['prgrmUrlRoot'] . 'fragments/' . $pget) . '/';
            }
        }

        $_SESSION['currentDisplay'] = preg_replace(":^\/:", '', $_SESSION['currentDisplay']);
        $_SESSION['currentDirPath'] = staticRoboUtils::fixPath($_SESSION['currentDirPath']);
        if ($this->dbg)
            $this->dbg();
    }

    function printDivs() {
        for ($i = 0; $i < $this->divcnt; $i++) {
            echo $this->divs[$this->topLevelDivNames[$i]];
        }
    }

    function crawl() {
        $ret = '';
        echo $this->printDivs();
        return $ret;
    }

    function createCSSLinks() {
        $ret = '';

        foreach ($this->cssfiles as $cssfile) {
            $ret .= "\n" . '<link rel="stylesheet" type="text/css" href="' . $cssfile . '"/>';
        }

        return ($ret);
    }

    function createJSLinks() {
        $ret = '';

        if (isset($this->jsfiles) && $this->jsfiles != null) {
            foreach ($this->jsfiles as $jsfile) {
                $ret .= "\n" . '<script type="text/javascript" src="' . $jsfile . '"></script>';
            }
        }

        return ($ret);
    }

    function getTitle()
    {
      $title='';
      $titleTest = $_SESSION['currentDirPath'] . 'roboresources/title';

      if(@stat($titleTest)){
        //echo "titleTest: " , $titleTest, "<br/>";
        $title = @file_get_contents($titleTest);
      }
      else if (isset($_GET['page']))
      {
         if(strstr(basename($_GET['page']), "index"))
         { 
            $title = dirname($_GET['page']);
            //echo "GET title1: ", $title, "<br/>";
         }else{
            $title = str_replace('/', ' ', staticRoboUtils::stripSuffix(basename($_GET['page'])));
            //echo "GET title2: ", $title, "<br/>";
         }
      }

      if(!isset($title))
            $title = 'Robopages'; 

      //echo " &nbsp; &nbsp; ", $title, "<br/>";
      return $title;
    }

    function startHTML() {
        global $sys_title;
        $title = $sys_title;

        $title = $this->getTitle();
$ret = <<<ENDO
<!DOCTYPE html>
<html lang="en">
  <head>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta property="og:type"   content="website" />
  <meta property="og:title"   content="DIY drift boat building" />
  <meta property="og:url"   content="http://montana-riverboats.com" />
  <meta name="verify-admitad" content="68d3884360" />
ENDO;
$ret .= '<title>'.  $title . "</title>";

        $ret .= $this->mkExtraHead();

        $ret .= $this->createCSSLinks();
        $ret .= $this->createJSLinks();
        $ret .= '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />';


        $ret .= '</head> ' . "\n\n" . '<body>' . "\n";

        return $ret;
    }

    function getCurrentDirFilenames($keywords) {
        $dir = $_SESSION['currentDirPath'];

        if ($dir_handle = opendir($dir)) {
            while (($file = readdir($dir_handle)) !== false) {
                if ($file != '.' && $file != '..' && !strstr($file, 'index') && !strstr($file, 'roboresources')) {
                    $keyword = preg_replace(":_|-:", ' ', staticRoboUtils::stripSuffix($file));
                    $keywords .= ',' . $keyword;
                }
            }
            closedir($dir_handle);
        }
        //echo "keywords: ", $keywords, "<br/>";
        return $keywords;
    }

    function mkExtraHead() {
        global $sys_defd, $sys_defk;
        $metadef = '';
        $ret = $keyswords = $metadef = $metakeys = '';
        if (isset($sys_defd))
            $metadef .= $sys_defd;

        if(isset($_GET['page']))
           $metadef .= ' -- ' . $_GET['page'];

        if (isset($sys_defk))
            $metakeys .= $sys_defk;

        // if(@stat($_SESSION['currentDirPath'] . 'keywords'))
        //  $metakeys .= file_get_contents($_SESSION['currentDirPath'] . 'keywords');

        $metakeys = $this->getCurrentDirFilenames($metakeys);

        $ret .= '<meta name="description" content="' . $metadef . '"/>' . "\n";
        $ret .= '<meta name="keywords" content="' . $metakeys . '"/>' . "\n";

        return $ret;
    }

    function getMimer() {
        return $this->mimer;
    }

}
