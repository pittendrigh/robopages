<?php

/*
unfinished utility for making a p2n file similar to the one
made by tocit.py
*/

include_once("../StaticRoboUtils.php");

$page = 1;
$rootPath = "/var/www/html/Github/robopages/fragments/Library/Flies/";
$page2numHash = array(); 

function processP2N()
{
    global $rootPath, $page2numHash;

    $pageNum = 1;
    $f = fopen("p2n", "w");
    $urlpath;
    foreach (array_keys($page2numHash) as $akey)
    {
         $urlpath = $page2numHash[$akey];
        //if($urlpath[0] == ".")
        //    continue;
        if(tocMimer($urlpath) == True)
        {
        $urlpath = str_replace($rootPath, '', $page2numHash[$akey]);
        $urlpath = preg_replace(":\/$:", '', $urlpath);
          fwrite($f, $urlpath . "\n");
          $pageNum = $pageNum + 1;
        }
     }
    fclose($f);
}


function doFile($indent, $filename)
{
    global $page, $page2numHash;

          if(tocMimer($filename))
          {      
              $page2numHash[$page] = $filename;      
              $page++;      
          }      
}

function tocMimer($path)
{
    $ret = false; 
    if (is_dir($path))
    {
        $ret = true;
    }
    else
    {
       $types = array( 
            "cap", "jpg", "JPG", "JPEG", "png", "gif", "htm", "html", "tgz",
            "zip", "pdf", "smil", "xml", "xhtml"
       ); 
    
       $filename = basename($path);
       $suffix = StaticRoboutils::getSuffix($filename); 
       if(isset($suffix) && $suffix != null && in_array($suffix, $types))
          $ret = true;
    }

   return $ret;    
}


function in_string($needle, $haystack, $insensitive = false) {
    $ret = null;
    if ($insensitive) {
        $ret = false !== stristr($haystack, $needle);
    } else {
        $ret = false !== strpos($haystack, $needle);
    }
    return $ret;
}

function doDir($path)
{
    if(!in_string("roboresorources",$path))
    {
        $indent = '';
        $slashes = substr_count($path, '/') - 2;
        for ($x=0; $x<$slashes; $x++)
        {
            $indent = $indent . '\t';
        }
        $typeFs = array(); 
        $dirs = array(); 
        if ($dir_handle = @opendir($path))
        {
                while (($name = readdir($dir_handle)) != false)
                {
    
                if($name[0] == '.')
                  continue;
                $checkThis = $path . '' . $name;
                if(is_dir($checkThis))
                {
                  if(!in_string("roboresources",$checkThis))
                  {
                    $dirs[] = $checkThis;
                  }
                }
                else
                {
                    $typeFs[] = $name;
                    #print("typeF: ", checkThis)
                }
             }
        }
        foreach ($typeFs as $file)
        {
            if ($file[0] == '.')
                continue;
            $joined = $path . $file;
            doFile($indent, $joined);
        }
        $dirCnt = count($dirs);
        for($x=0; $x<$dirCnt; $x++) 
        {
            $directory = $dirs[$x] . '/';
            if ($directory[0] == '.')
                continue;
            doFile($indent, $directory);
            doDir($directory);
        }
  }
}

doDir($rootPath);
processP2N();

?>
