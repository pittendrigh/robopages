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

  // makes a string hyperlink rather than new Link($line) object, for now anyway
  // better not rely on it but right now mkLink only called by assembleGlobalLinks
  // change highlighted so ... both global and selected local get hightlighted
  // albeit differently
  // need thisChapter and robopage
  function mkLink($url,$label)
  {
    $link = '';
    $chapter = $this->getThisChapter($url);
    $labelString = str_replace($_SESSION['bookTop'] . '/','',$url);
    $whereWeAreAtComparitor   = substr($labelString,0,strlen($labelString));
    $parentChapterHightlightFlag = ($chapter == $whereWeAreAtComparitor) ? TRUE : FALSE; 

    $linkTargetType = $this->mimer->getRoboMimeType($url);    
     
    $highLightFlag = FALSE;
    if(isset($_GET['robopage']) && $url == $_GET['robopage'])
        $highLightFlag = TRUE;
    $testAgain = $url . '/' . $_SESSION['currentDisplay'];

    // in bookNav we're only recognizing external links or internal book page links,
    // which reference a *.htm page or an internal directory (which defaults to an assumed *.htm)
    // internal directories may or may not be top level chapter directories
    if($linkTargetType == 'link') 
    {
        $link = '<a target="_blank" href="' 
         . $_SESSION['currentClickDirUrl'] . basename($url) . '">' . $label. '</a>'; 
    }
    else  // not an external link
    {
        if(isset($_GET['robopage']) && $url == $_GET['robopage'])
        { 
            // If this url from the p2n list is also the current robopage
            // whether it is in the top global chapter group or the bottom local chapter-pages group
            $link = '<a class="highlighted" href="?robopage='.$url.'">' 
              . $label . '</a>'."\n";
        }
        else 
        {
            // if the current robopage is a local chapter-page link 
            // we still, also want to highlight the chapter that contains that local link,
            // in the upper global chapters group
            //
            if($parentChapterHightlightFlag) 
             $link = '<a class="highlighted" href="?robopage='.$url.'">' 
                . $label . '</a>' . "\n";
            else
             $link = '<a href="?robopage='.$url.'">' . $label . '</a>' . "\n";
        }
    } 

    $link .= "\n";
    $this->allP2nLinks[$url] = $link;
    return($link);
  }

function assembleGlobalChapterLinks($linksString)
{
    $linkChunks = explode(",", $linksString);
    $cnt = count($linkChunks) -1;
    for($i=0; $i<$cnt; $i++)
    {
      $label = $subpath = $linkChunks[$i];
      if(strstr($linkChunks[$i], '|'))
      {
         $pieces = explode("|", $linkChunks[$i]);
         $subpath = $pieces[0];
         $label = $pieces[1];
      } 

      $url = $this->currentBookName . '/' . $subpath;
    
       if(is_dir($this->p2nFileDir . trim($linkChunks[$i])))
           $label = ' <i class="material-icons" style="font-size: 80%; ">folder</i> ' 
             . $label;
      $link = $this->mkLink($url, $label);
      $this->globalChapterLinks[] = $link;
    } 
  } 

  function getThisChapter()
  {

     // is a bookTop never in DOCUMENT_ROOT? No. need to fix this. grep -i actionItem *php
     $path = $_GET['robopage'];
     if(strstr(basename($path),'.'))
        $path = dirname($path);
     $chapter = basename ($path);    

     return($chapter);
  }

  function eraseChapterFromLine($path)
  {
     $isLeaf = FALSE;
     if (substr_count($path,'/') > 1)
       $isLeaf = TRUE;
     
     $chapter = $this->getThischapter($path);    
     $patt = $chapter . '/';
     $ret = preg_replace(":$patt:","",$path);

     if($isLeaf)
         $ret = ' &nbsp; &nbsp; &nbsp; ' . $ret;

     return ($ret);
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
    
      $label = $this->eraseChapterFromLine($line);

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

  function subPathIsValid($path)
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
      $charLen = strlen($chapterName);
      
      // top level directories below $_SESSION['bookTop'] are chapter names
      // We also want any leaf level *.htm files in the bookTop directory
      // strstr($line,'/') means this is inside a chapter
      // isValid means is_dir or is *.htm
      // last condition insures where are looking at lines in p2n for this chapter only
      if(strstr($line,'/') && $this->subPathIsValid($line) && substr($line,0, $charLen) == $chapterName)
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
      $bottom .= '<div id="bookNavBottom"><hr/>' ;
      $localLinksArray = $this->getLocalPageLinks();
      $cnt = count($localLinksArray);
      for($i=0; $i<$cnt; $i++)
      {
        $bottom .= $localLinksArray[$i];
      }
      $bottom .= '</div>';
    }
    $this->find_additional_pages();
    if($_SESSION['layout'] != 'bookGalleryNav')
    {
       foreach ($this->missedLinks as $alink)
       {
          $bottom .= $alink. "\n";
       } 
    }
    
    $bottom .= $this->nextPrevButtons->getOutput('');
    $ret =  $top . $bottom . '</div>';
    return($ret);
  }
}
?>
