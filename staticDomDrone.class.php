<?php

 include_once("domDrone.class.php");

 class staticDomDrone extends domDrone
 {


/*
  debugging echos below left for now--perhaps turned into a logging function at some later date
*/

   function copyDirContents($source, $dest)
   {
     if (is_dir($source))
     {
       $indexFlag = 0;
       $dir_handle = opendir($source);
       while ($file = readdir($dir_handle))
       {
         if ($file[0] == '.')
           continue;

         $suffix = strtolower(staticRoboUtils::getSuffix(basename($file)));
         $allowed = array("css", "html", "txt", "jpg", "png", "gif", "zip", "tgz");

         if (in_array($suffix, $allowed))
         {
           //echo "copying: ", $file, " to " , $dest, "<br/>";
           copy($source . '/' . $file, $dest . '/' . $file);
         }
       }
     }
   }

   function copyDirRecursively($source, $dest)
   {
     $indexFlag = 0;
     $allowed = array("css", "html", "txt", "jpg", "png", "gif", "zip", "tgz");

     //echo "copyDirRecursively($source, $dest) <br/>";
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
               if (!@stat($dest . "/" . $file, 0777))
               {
                 mkdir($dest . "/" . $file, 0777);
                 chmod($dest . "/" . $file, 0777);
               }
             }
             $this->copyDirRecursively($source . "/" . $file, $dest . "/" . $file);
           } else
           {
             if ($file == 'index.htm')
               $indexFlag++;

             $suffix = strtolower(staticRoboUtils::getSuffix(basename($file)));

             if (in_array($suffix, $allowed))
               copy($source . '/' . $file, $dest . '/' . $file);

             chmod($dest . "/" . $file, 0777);
           }
           if ($indexFlag == 0)
           {
             // something like PIC_4469_Avocet.jpg gets turned into a default display page in robopages
             // from $_SESSION['currentDisplay'] .....
             // how do we turn that into an index.htm
             // .....or do we do that somewhere else???
             // ah, this is only a possible problem if getcwd() . '/' . $_GET['robopage'] is_dir
           }
         }
       }
       closedir($dir_handle);
     } else
     {
       copy($source, $dest);
       chmod($dest, 0777);
     }
   }

   function EchoStatic($ddata, $mode)
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

     // grep -iH actionItem *php this NEEDS changed?  Make $sys_static_location a full path
     // pointing to OUTSIDE the currently executing index.php directory.
     //$staticDirPath = $sys_static_location . $_SESSION['currentDirUrl'];
     $staticDirPath = getcwd() . '/Pages/';

     // perhaps there are hand edited links in the data that need to be converted 
     $data = preg_replace('/\?robopage=|index.php\?robopage=/', '', $ddata);


     if (!@stat($staticDirPath))
     {
       mkdir($staticDirPath, 0777, true);
       chmod($staticDirPath, 0777);
     }

     // this copies only allowed files (no *.htm)
     $this->copyDirContents($_SESSION['currentDirPath'], $staticDirPath);

     if (@stat($_SESSION['currentDirPath'] . 'roboresources'))
     {

       if (!@stat($staticDirPath . 'roboresources'))
       {
         @mkdir($staticDirPath . 'roboresources', 0777);
         @chmod($staticDirPath . 'roboresources', 0777);
         $this->copyDirRecursively($_SESSION['currentDirPath'] . '/roboresources/', $staticDirPath . 'roboresources');
       }
     }


     $file = $_SESSION['currentDisplay'] == null ? 'index.html' : staticRoboUtils::stripSuffix($_SESSION['currentDisplay']) . '.html';

     $filepath = $staticDirPath . $file;

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

     //echo "dotsUp someUrl: ", $someUrl, "<br/>";
     $tmpArr = explode("/", $someUrl);

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

   //hhhhhref
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
       if (strstr($dynamicImgDestUrl, $adir))
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
       $href = $alink->getAttribute('href');
       $label = $alink->nodeValue;

       if (strstr($href, 'robopage='))
       {
         $aMungedHref = $this->relativeHref(preg_replace("/^.*=/", '', $href));
         if (strstr($href, "."))
         {
           $aMungedHref = staticRoboUtils::stripSuffix($aMungedHref) . ".html";
         }
         $alink->setAttribute("href", $aMungedHref);
         //echo "alink->getAttribute(href): ", $alink->getAttribute("href"), "<br/><br/>";
       }
     }

     $images = $dom->getElementsByTagName('img');
     foreach ($images as $anImg)
     {
       $imgSrc = $anImg->getAttribute('src');

       $aMungedSrc = $this->relativeSrc($imgSrc);
       $anImg->setAttribute("src", $aMungedSrc);
       //echo "anImg->getAttribute(src): ", $anImg->getAttribute("src"), "<br/><br/>";
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
       //@mkdir($sys_static_location . 'css', 0777);
       //@mkdir($sys_static_location . 'systemimages', 0777);
       @mkdir(getcwd() . '/Pages', 0777);
       @mkdir(getcwd() . '/Pages/css', 0777);
       @mkdir(getcwd() . '/Pages/systemimages', 0777);

       $this->copyDirContents(getcwd() . '/css', getcwd() . '/Pages/css');
       $this->copyDirContents(getcwd() . '/systemimages', getcwd() . '/Pages/systemimages');


       for ($i = 0; $i < $this->divcnt; $i++)
       {
         $data = $this->divs[$this->topLevelDivNames[$i]];
         $data = $this->mungeRobopageLinks($data);
         $this->EchoStatic($data, "a");
       }
     }
   }

 }

?>
