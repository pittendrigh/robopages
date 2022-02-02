<?php

  include_once("LinkedList.php");

  class p2nHandler
  {

    public $p2nFile;
    public $p2nFileDir;
    public $bookRootSubPath;
    public $mimer;
    // data
    public $url2PageNodeHash;    // stores nodes, see LinkedList.php
    public $additionalLinksHash; // stores HTML hyperlinks
    public $globalChapterLihks;  // to top level chapter dirs and htm files
    public $localChapterLinks;   // hyperlinks to files in current chapter 
    public $pageLinkedList;      // only partially used. Initializes nodes.

    function __construct()
    {
      $this->init();
    }

    function init()
    {
      //global $beenthere;
      $this->mimer = new RoboMimeTyper();
      $this->pageLinkedList = new LinkedList();
      $this->url2PageNodeHash = array();
      $this->additionalLinksHash = array();
      $this->globalChapterLinks = array();
      $this->localChapterLinks = array();

      //echo "p2nFile: ", $this->p2nFile, "<br/>";
      //echo "p2nFileDir: ", $this->p2nFileDir, "<br/>";
      //echo "bookRootSubPath: ", $this->bookRootSubPath, "<br/>";
      //echo "currentBookName: ", $this->currentBookName, "<br/><br/>";

      // now do it to it
      $this->setP2NFile();
      $this->p2nFileDir = trim(dirname($this->p2nFile) . '/');
      $this->readP2NFile();
      $this->createLinks();
      $this->find_additional_pages();
    }

    function readP2NFile($who = null)
    {
      $pageNum = -1;
      $lines = file($this->p2nFile);

      $lastDir = ' -- ';
      foreach ($lines as $aline)
      {
        $pageNum++;
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

        // 
        $this->pageLinkedList->ListAppend($pageNode);
        $this->url2PageNodeHash[$url] = $pageNode;
      }
    }

    function U2pDbg()
    {
      echo '<table style="margin-left: 2rem; font-size: 75%;">';
      echo '<tr><th> Prev </th><th> This </th><th> Next </th></tr>';
      foreach (array_keys($this->url2PageNodeHash) as $akey)
      {
        $node = $this->url2PageNodeHash[$akey];
        $prev = $next = ' -- ';
        if (isset($node->prev))
          $prev = $node->prev->dataObj;
        if (isset($node->next))
          $next = $node->next->dataObj;

        if (isset($node) && $node != null)
          echo "<tr><td>", $prev, "</td><td>", $node->idx, "</td><td><b>", $node->dataObj, "</b> </td><td>", $next, "</td></tr>";
      }
      echo "</table>";
    }


    function mmkLink($uurl, $llabel, $llinkClass=null)
    {
      //echo $uurl , " || ", $llabel, " <br/>";
      $link = $getRobopageComparitor = $linkClass = '';

      if(isset($llinkClass) && $llinkClass != null)
      {
        $linkClass = ' class="'. $llinkClass . '" ';
        //echo $linkClass, "<br/>";
      }

      // make sure bookRootSubPath does not get doubled up
      // and get rid of any double slashes //
      //echo "a: ", $uurl," ", $llabel, " <br/>";
      $label = trim(str_replace($this->bookRootSubPath, '', trim($llabel)));


      $url = trim(str_replace($this->bookRootSubPath, '', trim($uurl)));
      $url = $this->bookRootSubPath . $url;
      $url = StaticRoboUtils::fixroboPageEqualParm($url);
      //echo "b: ", $url," ", $label, " <br/>";

      $linkTargetType = $this->mimer->getRoboMimeType($url);

      // not an external link
      // if the current robopage is a local chapter-page link
      // we still, also want to highlight the chapter that contains that local link,
      // in the upper global chapters group
      $link = '<a ' . $linkClass . ' href="?robopage=' . $url . '">' . $label . '</a>' . "\n";
      $link .= "\n";

 
      return($link);
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

    function createLinks()
    {
      $linksString = '';
      $lines = file($this->p2nFile);
      $p2nLineCnt = count($lines);
      for ($i = 0; $i < $p2nLineCnt; $i++)
      {
        $line = trim($lines[$i]);
        $label = $subpath = $lines[$i];
        $label = str_replace($this->bookRootSubPath, '', $label);
        $url = $this->currentBookName . '/' . $subpath;

        if (!strstr($line, '/'))
        {
          if (is_dir($this->p2nFileDir . trim($lines[$i])))
          $label = ' <i class="material-icons">folder</i> '
                  . $label;

          $link = $this->mmkLink($url, $label);
          $this->globalChapterLinks[] = $link;
        }
        else
        {
           if (strstr($line, '/') && $this->subPathIsValid($line))
           {
             $link = $this->mmkLink($url, $label);
             $this->localChapterLinks[] = $link;
           }
        }

           
        }
      }

    function subPathIsValid($path)
    {
      $ret = FALSE;
      // this could work in numerous ways.
      // for now chapters only contain *.htm files or subdirectories
      // images are stored in roboresources/pics and ..thumbs ...slideshow
      if (strstr($path, '.htm'))
        $ret = TRUE;
      else if (is_dir($this->p2nFileDir . $path))
        $ret = TRUE;

      return($ret);
    }

    function mkLocalInThisChapterLinks()
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
        //$line = $this->bookRootSubPath . $line;

        // top level directories below $_SESSION['bookTop'] are chapter names
        // We also want any leaf level *.htm files in the bookTop directory
        // strstr($line,'/') means this is inside a chapter
        // isValid means is_dir or is *.htm
        // last condition insures where are looking at lines in p2n for this chapter only
        $charLen = strlen($chapterName);
        if (strstr($line, '/') && $this->subPathIsValid($line) && substr($line, 0, $charLen) == $chapterName)
        {
          $linksString .= $line . ',';
        }
      }

    }

    function findP2NFile($dir)
    {
      $dir = trim($dir);
      $ret = '';
      $checkThis = StaticRoboUtils::fixDoubleSlash($dir . '/p2n');
      if (@stat($checkThis))
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

// why not a link? ....same Gallery in two places?
        if (is_link($_SESSION['currentDirPath'] . $file))
        {
          continue;
        }

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
            $atest = @$this->url2PageNodeHash[$url];
            if (!$atest)
            {
              if (!isset($this->url2PageNodeHash[$url]) || $this->url2PageNodeHash[$url] == NULL)
              {
                $link = $this->mmkLink($url, basename($label), "extra");
                $this->additionalLinksHash[$url] = $link;
              }
            }
          }
        }
      }
    }

  }
  
