<?php

 @session_start();

 error_reporting(E_ALL);
 ini_set("display_errors", 1);

 include_once("conf/globals.php");
 require_once("StaticRoboUtils.php");
 require_once("plugins/roboMimeTyper.php");

 $plugins = file("conf/plugins.ini");
 $pcnt = count($plugins);
 for ($i = 0; $i < $pcnt; $i++)
 {
   $plugin = trim($plugins[$i]) . '.php';
   include_once("plugins/$plugin");
 }
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

   function __construct()
   {
     $this->mimer = new roboMimeTyper();
     $this->cssfiles = null;
     $this->jsfiles = null;
     $this->init();
     $this->readDefinitionFile();
   }

   function dbg()
   {
     print "definitionFile: " . $this->definitionFile . "<br/>";

     while (list($k, $v) = each($_SESSION))
     {
       print "$k s= $v<br/>";
     }
     print "<br/><br/>";
     while (list($k, $v) = each($_GET))
     {
       print "$k gg= $v<br/>";
     }
     print "<br/><br/>";
     while (list($k, $v) = each($_POST))
     {
       print "$k p= $v<br/>";
     }
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
       //  perhaps a URL like robopage=Honky-Dory/honky-dory-online-plans
       /* .....we want everything from Fly-Tying down?
         Fly-Tying/Sandy-Pittendrigh/BestOf|gallery
         Birds|gallery
         online|plans
        */
       for ($i = 0; $i < $dlcnt; $i++)
       {
         $tmp = explode("|", trim($dirlayoutLines[$i]));
         $this->dirlayouts[$tmp[0]] = $tmp[1];
       }

       $test = $_SESSION['prgrmDocRoot'] . $_GET['robopage'];
       if (!is_dir($test))
         $test = dirname($test);

       foreach (array_keys($this->dirlayouts) as $akey)
       {
         if (strstr($test, $akey))
         {
           $this->definitionFile = 'layouts/' . $this->dirlayouts[$akey] . '.xml';
         }
       }
     }

     // perhaps a current directory wide override exists, as  '/roboresources/layout'
     // ...this could conceivably override conf/layouts.ini
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/layout'))
     {
       $this->definitionFile = 'layouts/' . trim(file_get_contents($_SESSION['currentDirPath'] . 'roboresources/layout')) . '.xml';
     }

     // perhaps a page-specific override exists, which takes precedence
     // ...this would also override conf/layouts.ini
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/' . $_GET['robopage'] . '-layout'))
     {
       $this->definitionFile = 'layouts/' . trim(file_get_contents($_SESSION['currentDirPath'] . 'roboresources/' . $_GET['robopage'] . '-layout')) . '.xml';
     }

     // perhaps $_GET['layout'] exists, which takes precedence over all the above
     if (isset($_GET['layout']) && file_exists("layouts/" . $_GET['layout'] . '.xml'))
     {
       $this->definitionFile = 'layouts/' . $_GET['layout'] . '.xml';
     }


     // echo 'end of dirCrawler determineLayout: <b style="color: green;">', $this->definitionFile, "</b><br/>";
   }

   function init()
   {
     global $sys_layout;

     StaticRoboUtils::getpostClean();
     $this->determineLayout();

     if (isset($_GET['dbg']))
       $this->dbg = 1;

     $_SESSION['prgrmDocRoot'] = getcwd() . '/fragments/';

     $_SESSION['prgrmUrlRoot'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', getcwd() . '/');

     $this->setPathAndUrlParms();
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
     foreach ($this->layoutXML->xpath("/layout/jsfiles/link") as $anode)
     {
       if ($this->jsfiles == null)
         $this->jsfiles = array();
       $this->jsfiles[] = $anode;
     }
   }

   // will we ever need sub-elementName and not assume  div for subPlugins?
   function assembleContent($plugin, $divsrcs, $divid)
   {
     $ret = '';

     if (strstr($divsrcs, ","))
     {
       $pluginNames = explode(",", $divsrcs);
       $cnt = count($pluginNames) - 1;

       for ($i = 0; $i < $cnt; $i++)
       {
         $tmpStr = $pluginNames[$i];
         $divid = $subPluginName = $pluginNames[$i];

         if (strstr($tmpStr, ":"))
         {
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


     if (isset($plugin) && $plugin != null)
       $ret .= $plugin->getOutput($divid);

     return $ret;
   }

   function getPluginName($str)
   {
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
     if (!strstr($str, ","))
     {
       if (strstr($str, ":"))
       {
         $pieces = explode(":", $str);
         $ret = $pieces[1];
       }   // this else condition is already set as a default above
     } else
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
         //echo "divid: ", $divid, " pluginName: ", $ret, "<br/>";
       }
     }

     return $ret;
   }

   function doBlocksXML($adiv)
   {
     $ret = '';

     // elementName might be header footer div section etc
     $elementName = $adiv->getName();

     // divid is id= which may not be there
     $divid = trim($adiv[@id]);

     // optional klass= in the xml
     $divklass = trim($adiv[@klass]);
     $divsrc = $divsrcs = 'file';

     //echo $divid, " dirCrawler doBlocksXML definitionFile: <b>", $this->definitionFile, "</b><br/>";
     if (isset($adiv[@src]))
     {
       $divsrc = $this->getPluginName(trim($adiv[@src]));
       $divsrcs = trim($adiv[@src]);
     } else
     {
       $divsrc = $divsrcs = "container";
     }

     // now override the default divsrc if in $_GET
     if (isset($_GET[$divid]))
     {
       $divsrc = $divsrcs = $_GET[$divid];
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
         $plugin = new $divsrc();
         //echo $divid, " on ", $divsrc, "<br/>";
         //$dbg = $this->assembleContent($plugin, $divsrcs, $divid);
         //$ret .= $dbg;
         break;
     }
     foreach ($adiv->children() as $adivchild)
     {
       $ret .= $this->doBlocksXML($adivchild);
     }

     if (isset($plugin))
       $ret .= $this->assembleContent($plugin, $divsrcs, $divid);
     $ret .= '</' . $elementName . '>';
     return $ret;
   }

   function readDefinitionFile()
   {
     $this->divs = array();
     $this->topLevelDivNames = array();
     //echo $this->definitionFile, "<br/>";   
     $dfilepath = getcwd() . '/' . $this->definitionFile;
     //echo $dfilepath . "<br/>";
     $stuff = file_get_contents($dfilepath);
     //echo htmlentities($stuff). "<br/>" ;
     $this->layoutXML = simplexml_load_file($this->definitionFile);
     $this->processXMLCSSLines();
     $this->processXMLJSLines();
     foreach ($this->layoutXML->xpath("/layout/bodycontent/div") as $adiv)
     {
       $divid = trim($adiv[@id]);
       $this->topLevelDivNames[] = $divid;
       $this->divs[$divid] = $this->doBlocksXML($adiv);
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
       if ($file == '.' || $file == 'roboresources' || $file == 'admin' || $file == 'LOGS')
       {
         continue;
       }
       $linkType = $this->mimer->getRoboMimeType($dir . $file);
       $tarr = array('fragment', 'image', 'text');
       if (in_array($linkType, $tarr))
       {
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
     if (!isset($_GET['robopage']))
     {
       $_SESSION['currentDisplay'] = $this->getDefaultDisplay();

       $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'];
       $_SESSION['currentDirUrl'] = '';
       $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/';
     } 
     else
     {
       // there is a $_GET['robopage'], which might point to a directory
       $pget = isset($_GET['robopage']) ? $_GET['robopage'] : '';
       if (@is_dir($_SESSION['prgrmDocRoot'] . $pget))
         $pget .= '/';

       $test_is_dirpath = $_SESSION['prgrmDocRoot'] . $pget;
       //echo "test_is_dirpath: ", $test_is_dirpath, "<br/>";
       if (@is_dir($test_is_dirpath))
       {
         //echo "abc<br/>";
         $_SESSION['currentDisplay'] = '/' . $this->getDefaultDisplay();
         $_SESSION['currentDirPath'] = $test_is_dirpath;
         $_SESSION['currentDirUrl'] = $pget;
         $_SESSION['currentClickDirUrl'] = $_SESSION['prgrmUrlRoot'] . 'fragments/' . $pget;
         // if($pget[strlen($pget)-1] != '/')
         //    $pget .= '/';
       } else
       { // is a _GET['robopage'] that points to a leaf level file
         //echo "abcd<br/>";
         $test_dirname = dirname($_GET['robopage']) == '.' ? '' : dirname($pget) . '/';
         $test_dirname = $test_dirname == '/' ? '' : $test_dirname;
         $_SESSION['currentDisplay'] = '/' . basename($_GET['robopage']);
         $_SESSION['currentDirPath'] = $_SESSION['prgrmDocRoot'] . $test_dirname;
         $_SESSION['currentDirUrl'] = $test_dirname;
         $_SESSION['currentClickDirUrl'] = dirname($_SESSION['prgrmUrlRoot'] . 'fragments/' . $pget) . '/';
       }
     }

     $_SESSION['currentDisplay'] = preg_replace(":^\/:", '', $_SESSION['currentDisplay']);
     $_SESSION['currentDirPath'] = StaticRoboUtils::fixPath($_SESSION['currentDirPath']);


     $title = '';
     if(dirname($_SESSION['currentDirUrl']) != '')
	 $title = dirname($_SESSION['currentDirUrl']);

     if(isset($title[0]) && $title[0] == '.')
	$title = '';   
     $title .= $_SESSION['currentDisplay'];

     $_SESSION['title']  = preg_replace("/^.*_/","",$title );
     $_SESSION['title'] = staticRoboUtils::stripSuffix($_SESSION['title']);

     if ($this->dbg)
       $this->dbg();
 
   }
 

   function printDivs()
   {
     for ($i = 0; $i < $this->divcnt; $i++)
     {
       echo $this->divs[$this->topLevelDivNames[$i]];
     }
   }

   function createCSSLinks($static_mode)
   {
     $ret = '';
     $cssjsPathHack = str_replace($_SESSION['prgrmDocRoot'], '', $_SESSION['currentDirPath']);
     $slashCount = substr_count($cssjsPathHack, '/');
     //echo "css slashcount: ", $slashCount, " on  $cssjsPathHack<br/>";


     foreach ($this->cssfiles as $cssfile)
     {
       $ret .= "\n" . '<link rel="stylesheet" type="text/css" href="';

       if (isset($static_mode) && $static_mode)
       {
         //echo "<h1>cssjsPathHack: ", $cssjsPathHack, " slashCount: ", $slashCount, " </h1>";
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

         $ret .= "\n" . '"></script>';
       }
     }


     return ($ret);
   }

   function startHTML($sstatic_mode)
   {
     global $sys_nofollow, $sys_ogimage, $sys_ogurl;

     $static_mode = $sstatic_mode;

     // default title from last two parts of the QUERY_STRING as 
     // basename($_SESSION['currentDirUrl']) . '-'- . $_SESSION['currentDisplay']
     $title = $_SESSION['title'];

     // now prepend to exising default title if a page directory-wide title file is present
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/title')) 
     {
       $title = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/title') . '-' . $title;
     }

     // now override and replace the title if a page specific title is present
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/title-' . basename($_SESSION['currentDisplay'])))
     {
       $overridefile = $_SESSION['currentDirPath'] . 'roboresources/title-' . $_SESSION['currentDisplay'];
       $title = file_get_contents($overridefile);
     }
     $title = trim(preg_replace("/-|_/", " ", $title));

     //<META name="verify-admitad" content="xxxx" />

     $ret = '';
     $ret .= <<<ENDO
<!DOCTYPE html>
<html lang="en">
  <head>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-119784292-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-119784292-1');
</script>

  <META http-equiv="Content-Type" content="text/html;charset=utf-8"/>
  <META name="viewport" content="width=device-width, initial-scale=1.0"/>
  <META property="og:type"   content="website" />
  <META property="og:title"   content="$title" />
  <META property="og:url"   content="$sys_ogurl" />
  <META	property="og:image" content="$sys_ogimage" />
  <META name="google-site-verification" content="VfbG9hUocSivEqxyvW52qCqrjyRZGGZidif018AEMrw" />
ENDO;

     $ret .= "\n" . '<title>' . $title . '</title>';
     $ret .= "\n";

     // set conf/globals.php $sys_nofollow=TRUE for test subdomains  
     /*
       if(isset($sys_nofollow) && $sys_nofollow == TRUE)
       $ret .= '<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">';
      */
     $ret .= $this->mkExtraHead();
     $ret .= $this->createCSSLinks($static_mode);
     $ret .= $this->createJSLinks($static_mode);
     $ret .= "\n" . '<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />';
     $ret .= "\n" . '</head> ' . "\n\n" . '<body>' . "\n";
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
     //echo "keywords: ", $keywords, "<br/>";
     return $keywords;
   }

   function mkExtraHead()
   {
     global $sys_defd, $sys_defk;

     $ret = $keyswords = $metadesc = $metakeys = '';

     // make a default $metadesc and hope to override it a few lines later
     $metadesc = "Robopages CMS ";
     if (isset($sys_defd))
     {
       $metadesc = $sys_defd;
       if (isset($_GET['robopage']))
         $metadesc .= '  ' . basename($_GET['robopage']);
     }
     // now override $metadesc if roboresources/metadesc exists
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/metadesc'))
     {
       $metadesc = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metadesc');
     }

     // same with keys
     $metakeys = "robopages,fast,cms";
     if (isset($sys_defk))
       $metakeys = $sys_defk;
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/metakeys'))
     {
       $metakeys = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metakeys');
     } else
     {
       if (isset($_GET['robopage']))
         $metakeys .= "," . str_replace('/', ',', $_GET['robopage']);
     }

     // now check for page-specific overrides
     $fileBase = StaticRoboUtils::stripSuffix(basename($_SESSION['currentDisplay']));
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/metakeys-' . $fileBase))
     {
       $metakeys = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metakeys-' . $fileBase);
     }
     if (@stat($_SESSION['currentDirPath'] . 'roboresources/metadesc-' . $fileBase))
     {
       $metadesc = file_get_contents($_SESSION['currentDirPath'] . 'roboresources/metadesc-' . $fileBase);
     }

     $metakeys = preg_replace('/-|_/', ',', trim($metakeys));
     $metadesc = trim($metadesc);

     $ret .= "\n" . '<META name="description" content="' . $metadesc . '"/>' . "\n";
     $ret .= '<META name="keywords" content="' . $metakeys . '"/>' . "\n";

     return $ret;
   }

 }
 
