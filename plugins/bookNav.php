<?php

@session_start();
include_once("plugin.php");
include_once("nextPrevButtons.php");
include_once("roboMimeTyper.php");
include_once("dynamicNavigation.php");

class bookNav extends plugin 
{
  protected   $nextPrevButtons;
  protected   $p2nFileDir;
  protected   $p2nFile;
  protected   $currentBookName;
  protected   $mimer;
  protected   $allP2nLinks;
  protected   $missedLinks;
  protected   $globalChapterLinks;

  function _construct()
  {
     $this->init();
  }

  function parentDir($testPath)
  {
      //echo "testpath: ", $testPath, "<br/>";
      if(strstr($testPath,'htm'))
      { 
        $testPath = dirname($testPath);
        //echo " ttestpath: ", $testPath, "<br/>";
      } 
      $ret = basename(dirname($testPath));
      //echo "ret: ", $ret, "<br/>";
      return ($ret);
  }
  // makes a string hyperlink rather than new Link($line) object, for now anyway
  // better not rely on it but right now mkLink only called by assembleGlobalLinks
  function mkLink($url,$label)
  {
    $link = '';

    $linkTargetType = $this->mimer->getRoboMimeType($url);    
     
    $highLightFlag = FALSE;
    if(isset($_GET['robopage']) && $url == $_GET['robopage'])
        $highLightFlag = TRUE;
    $testAgain = $url . '/' . $_SESSION['currentDisplay'];
 
    switch($linkTargetType)
    {
      case "link":
        $link = '<a target="_blank" href="' 
         . $_SESSION['currentClickDirUrl'] . basename($url) . '">' . $label. '</a>'; 
      break;
      default:
      if(isset($_GET['robopage']) && $url == $_GET['robopage'])
      { 
        $link = '<a class="highlighted" href="?robopage='.$url.'">' 
          . $label . '</a>'."\n";
      }
      else
      {
        if($this->parentDir($_GET['robopage']) == basename($url)) 
         $link = '<a class="highlighted" href="?robopage='.$url.'">' 
            . $label . '</a>' . "\n";
        else
         $link = '<a href="?robopage='.$url.'">' . $label . '</a>' . "\n";
      }
    } 

    $this->allP2nLinks[$url] = $link;
    return($link);
  }

  function assembleGlobalChapterLinks($linksString)
  {
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $url = $this->currentBookName . '/' . $linkChunks[$i];
    
       if(is_dir($this->p2nFileDir . trim($linkChunks[$i])))
           $label = ' <i class="material-icons" style="font-size: 80%; ">folder</i> ' 
             . $linkChunks[$i];
       else
            $label = $linkChunks[$i];
      $link = $this->mkLink($url, $label);
      $this->globalChapterLinks[] = $link;
    } 
  } 


  function chaptersPageLinkLabel($testpath)
  {
     $chunks = explode('/',$testpath);
     $lastChunk = '';
     if(isset($chunks[1]))
        $lastChunk = StaticRoboUtils::mkLabel($chunks[1]);
     return $chunks[0] . '/' . $lastChunk;
  }

  function assembleLocalPageLinks($linksString)
  {
    $returningLeafLinks = array();
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $line = trim($linkChunks[$i]);
      $url = $this->currentBookName . '/' . $line;
    
      // need to support optional label option in link line  grep -i actionItem *php
      $label = StaticRoboUtils::mkLabel(preg_replace(":^.*?/:","",$line));
      //$label = $this->chaptersPageLinkLabel($label);

      if(isset($_GET['robopage']) && $_GET['robopage'] == $url 
          || strstr($label,  $_SESSION['currentDirUrl'] .  $_SESSION['currentDisplay']))
        $link = '<a class="lclhighlighted" href="?robopage='.$url.'">' 
          . $label . '</a>';
       else
          $link = '<a href="?robopage='.$url.'">' . $label . '</a>';

      $this->allP2nLinks[$url] = $link;
      $returningLeafLinks[] = $link;
    }

    return($returningLeafLinks);
  }

  function getGlobalChapterLinks()
  {
    $linksString = '';
    $lines = file($this->p2nFile);
    $p2nLineCnt = count($lines);
    for($i=0; $i<$p2nLineCnt; $i++)
    {
      $line = trim($lines[$i]);
      $tentativeDirPath = trim($this->p2nFileDir) .  trim($line);

      // top level directories are chapter names
      // we also want any leaf level *.htm files in the bookTop directory
      if(!strstr($line,'/'))
      {
          $linksString .= $line . ',';
      }
    }

    $this->assembleGlobalChapterLinks($linksString);
  }

  function subPathIsLeaf($path)
  {
    $ret=FALSE;
    // this could work in numerous ways.
    // chapters only contain *.htm files or subdirectories
    if(strstr($path,'.htm'))
     $ret = TRUE;
    else if (is_dir($this->p2nFileDir . $path))
     $ret = TRUE;

    return($ret);
  }

  function getLocalPageLinks()
  {
    $linksString = $chaptername = '';
    $lines = file($this->p2nFile);
    $p2nLineCnt = count($lines);
    
    $robopage = '';
    if(isset($_GET['robopage']))
    {
         $robopage = $_GET['robopage'];
         if(!is_dir($_SESSION['prgrmDocRoot'] . $robopage))
           $robopage = dirname($robopage) . '/';
    }
    $chapterName = 
       str_replace($this->p2nFileDir, "", $_SESSION['prgrmDocRoot'] . $robopage);

    for($i=0; $i<$p2nLineCnt; $i++)
    {
      $line = trim($lines[$i]);

      // top level directories are chapter names
      // we also want any leaf level *.htm files in the bookTop directory
      if(strstr($line,'/')&&$this->subPathIsLeaf($line)&&strstr($line,$chapterName))
      {
          $linksString .= $line . ',';
      }
    }

    return($this->assembleLocalPageLinks($linksString));
  }

  function findP2NFile($dir)
  {
     $dir = trim($dir);
     $ret = '';
     if(@stat($dir. '/p2n'))
     {
        return $dir . '/p2n';
     }
     else if(!strstr($dir, 'fragments'))
     {
       echo "no p2n file found<br/>";
       echo "redirect to an error page<br/>";
       exit;
       return '';
     }
     else
     {
        $ret = trim($this->findP2NFile(dirname($dir)));
        return $ret;
     }
  }

  function setP2NFile()
  {
    $this->p2nFile = $this->findP2NFile($_SESSION['currentDirPath']);
    $this->p2nFileDir = dirname($this->p2nFile) . '/';
    $this->currentBookName = preg_replace(":\/$:",'',
      str_replace($_SESSION['prgrmDocRoot'],"" ,$this->p2nFileDir));
    $_SESSION['bookTop'] = $this->currentBookName; 

  }

  function init()
  {
    $this->mimer = new roboMimeTyper();
    $this->setP2NFile();
    $this->nextPrevButtons = new nextPrevButtons();

    $this->missedLinks = array();
  }

  function inBookTopDir()
  {
    $ret = FALSE;
    if($_SESSION['currentDirPath'] == $this->p2nFileDir)
      $ret = TRUE;

    return $ret;
  }

  function getTOCJs()
  {
    $ret = '';
    $ret .= <<<ENDO
<script>
    function tocToggle()
    {
      var x = document.getElementById("ttoc");
      var b = document.getElementById("tcdo");
      if (x.style.display === "none")
      {
        x.style.display = "block";
        b.innerHTML="toc";
      }
      else
      {
        x.style.display = "none";
        b.innerHTML="TOC";
      }
     }
</script>
ENDO;

    return $ret;
  }

  function getDirlinksPath()
  {
     $ret = $this->p2nFile;
     return($ret);
  }

  function getLines($path)
  {
     $lines = $allLines = array();
     $allLines = file($path);
     $lineCnt = count($allLines);
     for($i=0; $i<$lineCnt; $i++)
     {
       if(strstr($allLines[$i], $_GET['robopage']))
       {
         $lines[] = $allLines[$i]; 
       }
     }
     return($lines);
  }

  function find_additional_pages()
  {
      global $sys_show_suffixes, $sys_thumb_links;

      $linkTargetType = "unknown";

      $handle = @opendir($_SESSION['currentDirPath']);
      while ($handle && ($file = @readdir($handle)) !== FALSE)
      {
          if ($file[0] == '.')
              continue;
          else if (strstr($file, ".frag") || $file == 'roboresources'  
              || $file == 'dirlinks')
              continue;

          // why not a link?
          // if (is_link($this->currentDirPath . $file)) { continue; }

          $label = ucfirst($file);
          if (!$sys_show_suffixes)
              $label = ucfirst(StaticRoboUtils::stripSuffix($file));

          $linkTargetType = 
              $this->mimer->getRoboMimeType($_SESSION['currentDirUrl'] . $file);

          $url = '';
          if (isset($linkTargetType) && $linkTargetType != "unknown")
          {
              $url = 
                StaticRoboUtils::fixrobopageEqualParm($_SESSION['currentDirUrl'] 
                  . $file);

              if ($linkTargetType == 'link')
              {
                  $url = $_SESSION['currentClickDirUrl'] . $file;
              }
              else
              {
                $atest = @$this->allP2nLinks[$url];
                  if (!isset($atest) || $atest == null)
                  {
                    $link = '<a href="?robopage=' . $url. '"><b class="redAlert">' 
                      . $label. "</b></a>\n";
                    $this->missedLinks[$url] = $link;
                  }
              }
          }
          //else{ echo "??? $file<br/>\n"; }
      }
  }

  function getOutput($divid)
  {
    $ret = $top = $bottom = '';

    $top .= '<button id="tcdo" onClick="tocToggle()">toc</button>';
    $top .= $this->getTOCJs();
    $top .= $this->nextPrevButtons->getOutput('');
    $top .= '<div id="ttoc">';

    $this->getGlobalChapterLinks();
    $cnt = count($this->globalChapterLinks);
    for($i=0; $i<$cnt; $i++)
    {
       $top .= $this->globalChapterLinks[$i];
    }

    if(!$this->inBookTopDir())
    {
      $bottom .= "<hr/>";
      $localLinksArray = $this->getLocalPageLinks();
      $cnt = count($localLinksArray);
      for($i=0; $i<$cnt; $i++)
      {
        $bottom .= $localLinksArray[$i];
      }

    }
    $this->find_additional_pages();
    if($_SESSION['layout'] != 'bookGalleryNav')
    {
       foreach ($this->missedLinks as $alink)
       {
          $bottom .= $alink. "\n";
       } 
    }
    
    $ret =  $top . $bottom . '</div>';
    return($ret);
  }
}
?>
