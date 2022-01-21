<?php

  class p2nHandler
  {
    public $p2nFile;
    public $p2nFileDir;
    public $bookRootSubPath;
    public $url2PageNodeHash;
    public $pageNum2NodeHash;
    public $orderedP2NUrls;
    public $additionalLinksHash;
    public $pageLinkedList;
    public $mimer;
    public $urlCount;
    public $allP2NLinks;
    public $globalChapterLihks;

    function __construct()
    {
      $this->mimer = new roboMimeTyper();
      $this->setP2NFile();
      $this->missedLinks = array();
      $this->allP2NLinks = array();
      $this->mimer = new RoboMimeTyper();
      $this->url2PageNodeHash = array();
      $this->pageNum2NodeHash = array();
      $this->orderedP2NUrls = array();
      $this->additionalLinksHash = array();
      $this->globalChapterLinks = array();
      $this->pageLinkedList = new LinkedList();

      $this->init();
    }

    function init()
    {
      $this->setP2NFile($_SESSION['currentDirPath']);
      $this->p2nFileDir = trim(dirname($this->p2nFile) . '/');
      //echo "p2nFile: ", $this->p2nFile, "<br/>";
      //echo "p2nFileDir: ", $this->p2nFileDir, "<br/>";
      //echo "bookRootSubPath: ", $this->bookRootSubPath, "<br/>";
      //echo "currentBookName: ", $this->currentBookName, "<br/><br/>";
      $this->setP2NFile();
      $this->readP2NFile();
      $this->getGlobalChapterLinks();
      $this->find_additional_pages();
      $this->urlCount = 0;
      //$this->U2pDbg();
    }

    function readP2NFile($who = null)
    {
      $pageNum = -1;
      $lines = file($this->p2nFile);

      $lastDir = ' -- ';
      foreach ($lines as $aline)
      {
        $pageNum ++;
        $aline = trim($aline);
        $url = $this->bookRootSubPath . trim($aline);

        $testDirPath = $this->p2nFileDir . $aline;
        //if(!is_dir($testDirPath) || $testDirPath == $lastDir)
        if (!is_dir($testDirPath))
        {
          $testDirPath = dirname($testDirPath);
          if ($lastDir == $testDirPath)
          {
            $lastDir = "--";
          }
          $pageNode = new node($url, null, null, $pageNum);
        }
        else // is_dir
        {
          if ($lastDir != $testDirPath)
          {
            $lastDir = $testDirPath;
          }
          
          $url = $this->bookRootSubPath . trim($aline);
          $pageNode = new node($url, null, null, $pageNum);
        }

        $this->pageLinkedList->ListAppend($pageNode);
        $this->url2PageNodeHash[$url] = $pageNode;
        $this->orderedP2NUrls[$pageNum] = $pageNode;
        $this->pageNum2NodeHash[$this->urlCount] = $pageNode;
        $this->urlCount++;
      }
    }

    function u2pDbg()
    {
      echo '<table style="font-size: 50%;">';

      //for($i=0; $i < $this->urlCount; $i++)
      for ($i = 0; $i < 10; $i++)
      {
        //$key = $this->orderedP2NUrls[$i];
        //$node = $this->url2PageNodeHash[$key];
        $node = $this->orderedP2NUrls[$i];
        $prev = $next = ' -- ';
        if (isset($node->prev))
          $prev = $node->prev->dataObj;
        if (isset($node->next))
          $next = $node->next->dataObj;

        echo "<tr><td>", $prev, "</td><td>", $node->idx, "</td><td><b>", $node->dataObj, "</b> </td><td>", $next, "</td></tr>";
      }
      echo "</table>";
    }

    function mmkLink($uurl, $label)
    {

      // zap eventually? Guard against double double prefix
      $url = str_replace($this->bookRootSubPath,'',trim($uurl));
      $url = $this->bookRootSubPath . $url;

      $link = $getRobopageComparitor = '';
      $url = StaticRoboUtils::fixroboPageEqualParm($url);
      if (isset($_GET['robopage']))
      {
        $getRobopageComparitor = StaticRoboUtils::fixroboPageEqualParm($_GET['robopage']);
      }

      $chapter = $this->getThisChapter($url);

      $labelString = str_replace($_SESSION['bookTop'] . '/', '', $url);

// ?????????????
      $whereWeAreAtComparitor = substr($labelString, 0, strlen($labelString));

      $linkTargetType = $this->mimer->getRoboMimeType($url);

      $highlightFlag = FALSE;
      $hightlightFlag = ($chapter == $whereWeAreAtComparitor) ? TRUE : FALSE;

// can still set $highlightFlag TRUE yet again
      if (isset($getRobopageComparitor) && $getRobopageComparitor == $url || stristr($label, $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']))
      {
        $highlightFlag = TRUE;
      }

// in roboBook we're only recognizing external links or internal book page links
// referencing a *.htm page or internal directory (defaults to an assumed *.htm)
// internal directories may or may not be top level chapter directories
      $linkClass = '';
      if ($highlightFlag == TRUE)
        $linkClass = ' class="highlighted" ';

      if ($linkTargetType == 'link')
      {
        $link = '<a ' . $linkClass . ' target="_blank" href="'
                . $_SESSION['currentClickDirUrl'] . basename($url) . '">' . $label . '</a>';
      }
      else
      {  // not an external link
         // if the current robopage is a local chapter-page link
         // we still, also want to highlight the chapter that contains that local link,
         // in the upper global chapters group
        $link = '<a ' . $linkClass . ' href="?robopage=' . $url . '">' . $label . '</a>' . "\n";
      }

      $link .= "\n";

      return($link);
    }

    function assembleGlobalChapterLinks($linksString)
    {
      $linkChunks = explode(",", $linksString);
      $cnt = count($linkChunks) - 1;
      for ($i = 0; $i < $cnt; $i++)
      {
        $label = $subpath = $linkChunks[$i];
        $label = str_replace($this->bookRootSubPath,'',$label);

        // ouch. I think '|' is a not documented or commented
        // alternate label mechanism
        if (strstr($linkChunks[$i], '|'))
        {
          $pieces = explode("|", $linkChunks[$i]);
          $subpath = $pieces[0];
        }

        $url = $this->currentBookName . '/' . $subpath;

        if (is_dir($this->p2nFileDir . trim($linkChunks[$i])))
          $label = ' <i class="material-icons" style="font-size: 80%; ">folder</i> '
                  . $label;

        //$url = $this->bookRootSubPath . $url;
        $link = $this->mmkLink($url, $label);
        $this->allP2NLinks[$url] = $link; //xxxxxxxxx 
        $this->globalChapterLinks[] = $link;
      }
    }

    function getThisChapter()
    {
      $path = $chapter = '';
// is a bookTop never in DOCUMENT_ROOT? No. need to fix this. grep -i actionItem *php
      if (isset($_GET['robopage']) && $_GET['robopage'] != null)
      {
        $path = $_GET['robopage'];
        if (strstr(basename($path), '.'))
          $path = dirname($path);
        $chapter = basename($path);
      }

      return($chapter);
    }

// not just basename($oath), as in sub-directory of chapters
    function eraseChapterFromLine($path)
    {
      $isLeaf = FALSE;
      if (substr_count($path, '/') > 1)
        $isLeaf = TRUE;

      $chapter = $this->getThischapter($path);
      $patt = $chapter . '/';
      $ret = preg_replace(":$patt:", "", $path);

      if ($isLeaf)
        $ret = ' &nbsp; &nbsp; &nbsp; ' . $ret;

      return ($ret);
    }

    function assembleLocalPageLinks($linksString)
    {
      $returningLeafLinks = array();
      $linkChunks = explode(",", $linksString);
      $cnt = count($linkChunks) - 1;
      for ($i = 0;
              $i < $cnt;
              $i++)
      {
        $line = trim($linkChunks[$i]);
        $url = $this->currentBookName . '/' . $line;

        $label = $this->eraseChapterFromLine($line);

        if (isset($_GET['robopage']) && $_GET['robopage'] == $url || strstr($label, $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']))
          $link = '<a class="lclhighlighted" href="?robopage=' . $url . '">' . $label . '</a>';
        else
          $link = '<a href="?robopage=' . $url . '">' . $label . '</a>';

        $this->allP2NLinks[$url] = $link;
        $returningLeafLinks[$url] = $link;
      }

      return($returningLeafLinks);
    }

    // Makes a string to explode later
    // Might be cleaner to have one more hashed array
    // Said string is the contents of p2n, which are all value $_GET['robopage'] values mapping to plages
    // Any robopage value might be an empty dirctory name,
    // which would resolve to a default page with $_SESSION['currentDisplay']
    function getGlobalChapterLinks()
    {
      $linksString = '';
      $lines = file($this->p2nFile);
      $p2nLineCnt = count($lines);
      for ($i = 0; $i < $p2nLineCnt; $i++)
      {
        $line = trim($lines[$i]);
        $lineTest = $line;
        $line = $this->bookRootSubPath . $line;

        // top level directories are chapter names and have no path slashes
        // but we do also want any leaf level *.htm files in the bookTop directory
        if (!strstr($lineTest, '/'))
        {
          $linksString .= $line . ',';
        }
      }

      $this->assembleGlobalChapterLinks($linksString);
    }

    function subPathIsValid($path)
    {
      $ret = FALSE;
      // this could work in numerous ways.
      // for now chapters only contain *.htm files or subdirectories
      // images are stored in roboresources/pics and ..thumbs ...slideshow
      // chapters might contain a Gallery, that is not part of next prev
      // Galleries can only be entered from TOC links
      if (strstr($path, '.htm'))
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
      if (isset($_GET['robopage']))
      {
        $robopage = $_GET['robopage'];
        if (!is_dir($_SESSION['prgrmDocRoot'] . $robopage))
          $robopage = dirname($robopage) . '/';
      }
      $chapterName = str_replace($this->p2nFileDir, "", $_SESSION['prgrmDocRoot'] . $robopage);

      for ($i = 0; $i < $p2nLineCnt; $i++)
      {
        $line = trim($lines[$i]);
        $line = $this->bookRootSubPath . $line;
        $charLen = strlen($chapterName);

        // top level directories below $_SESSION['bookTop'] are chapter names
        // We also want any leaf level *.htm files in the bookTop directory
        // strstr($line,'/') means this is inside a chapter
        // isValid means is_dir or is *.htm
        // last condition insures where are looking at lines in p2n for this chapter only
        if (strstr($line, '/') && $this->subPathIsValid($line) 
             && substr($line, 0, $charLen) == $chapterName)
        {
          $linksString .= $line . ',';
          $this->allP2NLinks[$line] = $this->mmkLink($line, $line);
        }
      }

      return($this->assembleLocalPageLinks($linksString));
    }

    function findP2NFile($dir)
    {
      $dir = trim($dir);
      $ret = '';
      $checkThis = StaticRoboUtils::fixDoubleSlash($dir . '/p2n');
      if(@stat($checkThis))
      {
        return $checkThis;
      }
      else if (!strstr($dir, 'fragments'))
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
      $this->currentBookName = preg_replace(":\/$:", '',
              str_replace($_SESSION['prgrmDocRoot'], "", $this->p2nFileDir));
      $this->bookRootSubPath = StaticRoboutils::removeLeadingPathSlash(str_replace($_SESSION['prgrmDocRoot'], '/', $this->p2nFileDir));
      $_SESSION['bookTop'] = $this->currentBookName;
    }

    function inBookTopDir()
    {
      $ret = FALSE;
      if ($_SESSION['currentDirPath'] == $this->p2nFileDir)
        $ret = TRUE;

      return $ret;
    }

    function find_additional_pages()
    {
      global $sys_show_suffixes, $sys_thumb_links;

      $linkTargetType = "unknown";

      $handle = @opendir($_SESSION['currentDirPath']);
      while ($handle && ($file = @readdir($handle)) !== FALSE)
      {
        if ($file[0] == '.')
        {
          continue;
        }
        else if (strstr($file, ".frag") || $file == 'roboresources' || $file == 'dirlinks')
        {
          continue;
        }


// why not a link?
        if (is_link($_SESSION['currentDirPath'] . $file)) { continue; }

        $label = basename($file);
        $linkTargetType = $this->mimer->getRoboMimeType($_SESSION['currentDirUrl'] . $file);
        $url = '';

        if (isset($linkTargetType) && $linkTargetType != "unknown")
        {
          $url = StaticRoboUtils::fixroboPageEqualParm($_SESSION['currentDirUrl']
                          . $file);

          // the following a link in the "is downloadable" sense
          if ($linkTargetType == 'link')
          {
            $url = $_SESSION['currentClickDirUrl'] . $file;
          }
          else // not a roboMimeType link
          {
            $atest = @$this->allP2NLinks[$url];
            if(!$atest)
            {
              $link = $this->mmkLink($url, basename($label));
              $link = preg_replace(":href:", 'class="extra" href', $link);

              if(!isset($this->allP2NLinks[$url] ) || $this->allP2NLinks[$url] == NULL)
                  $this->additionalLinksHash[$url] = $link;
            } else {
              foreach(array_keys($this->allP2NLinks) as $akey){
            }

            } 
          }
        }
      }
    }

  }
  
