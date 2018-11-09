<?php

 @session_start();

 include_once("conf/globals.php");
 include_once("Link.php");

 class navigationNode
 {

   public $prevUrl;
   public $nextUrl;
   public $thisUrl;
   public $prevId;
   public $nextId;
   public $thisId;

   function __construct()
   {

     /*
       $this->prevUrl = $p;
       $this->nextUrl = $n;
       $this->thisUrl = $u;
      */
     ////$this->dbg();
   }

   function dbg()
   {
     echo "prev: ", $this->prevUrl, "<br/>";
     echo "this: ", $this->thisUrl, "<br/>";
     echo "next: ", $this->nextUrl, "<br/>";
   }

 }

 class bookTOC
 {

   public $pathKludge;
   public $opfDirPath;
   public $opfDirUrl;
   public $opfFilePath;
   public $tocFilePath;
   public $id2UrlHash;
   public $basename2UrlHash;
   public $basename2NodeHash;

   function __construct()
   {
     $this->init();
   }

   function rsearch($folder, $pattern)
   {
     $dir = new RecursiveDirectoryIterator($folder);
     $ite = new RecursiveIteratorIterator($dir);
     $files = new RegexIterator($ite, $pattern, RegexIterator::GET_MATCH);
     $fileList = array();
     foreach ($files as $file)
     {
       $fileList = array_merge($fileList, $file);
     }
     return $fileList;
   }

   function init()
   {
     $this->id2UrlHash = array();
     $this->basename2UrlHash = array();
     $this->setOpfFilePath();
     $this->processOpfFile();
     $this->mkPageNodesHash();
   }

   function setOpfFilePath()
   {
     $d = new DOMDocument;
     $d->preservewhiteSpace = FALSE;

     $d->loadXML(file_get_contents($_SESSION['currentDirPath'] . 'META-INF/container.xml'));
     $d->preserveWhiteSpace = false;
     $xpath = new DOMXpath($d);
     $xpath->registerNamespace('x', 'urn:oasis:names:tc:opendocument:xmlns:container');
     $nodes = $xpath->query("//x:rootfile");


     $this->pathKludge = dirname($nodes[0]->getAttribute('full-path'));
     $_SESSION['pathKludge'] = $this->pathKludge;

     //echo "pathKludge: ", $this->pathKludge, "<br/>";
     $this->opfFilePath = $_SESSION['currentDirPath'] . $nodes[0]->getAttribute('full-path');
     //echo "opfFilePath: ", $this->opfFilePath, "<br/>";
     $this->opfDirPath = dirname($this->opfFilePath) . '/';
     $this->opfDirUrl = dirname($_SESSION['currentDirUrl'] . $nodes[0]->getAttribute('full-path'));

     //echo  "opfDirPath: ", $this->opfDirPath. "<br/>";
     //echo  "opfDirUrl: ", $this->opfDirUrl. "<br/>";
     //echo  "opfFilePath: ", $this->opfFilePath. "<br/>";
     $_SESSION['opfDirPath'] = $this->opfDirPath;
     $_SESSION['opfDirUrl'] = $this->opfDirUrl;
   }

   function processOpfFile()
   {
     $d = new DOMDocument;
     $d->preservewhiteSpace = FALSE;
     //echo "processOpfFile with ", $this->opfFilePath, "<br/>";
     $d->loadXML(file_get_contents($this->opfFilePath));
     $d->preserveWhiteSpace = false;
     $xpath = new DOMXpath($d);
     $xpath->registerNamespace('x', 'http://www.idpf.org/2007/opf');

     $nodes = $xpath->query("//x:item[contains(@properties,'nav')]");
     $tocHref = $nodes[0]->getAttribute('href');
     //echo "tocHref: ", $tocHref, "<br/>";

     $this->tocFilePath = $this->opfDirPath . $tocHref;
     $kludge = $this->pathKludge . '/' . $this->pathKludge;
     $this->tocFilePath = preg_replace(":$kludge:", $this->pathKludge, $this->opfDirPath . $tocHref);

     //echo "tocFilePath: ", $this->tocFilePath, "<br/>";

     $nodes = null;
     $nodes = $xpath->query("//x:manifest/x:item");
     foreach ($nodes as $node)
     {
       $href = $node->getAttribute('href');
       $id = $node->getAttribute('id');

       $baseKey = basename($href);
       //echo " id2UrlHash [$id] as ".basename($href)."<br/>";
       $this->id2UrlHash[$id] = basename($href);

       $this->basename2UrlHash[$baseKey] = preg_replace("/\.\.\//", '', $href);
     }
   }

   function processLi($liNode)
   {
     $ret = '<li>';
     foreach ($liNode->children() as $child)
     {
       $href = $childHref = $hrefKey = '';
       if ($child->getName() == 'a' && $child['href'] != null)
       {
         $childHref = $child['href'];
         $hrefKey = preg_replace("/#.*/", '', $childHref);
         if (isset($this->basename2UrlHash[$hrefKey]) && $hrefKey != null)
         {
           $hashedHref = $this->basename2UrlHash[$hrefKey];
           $hrefChunks = explode("#", $childHref);
           $rightSide = '';
           if (isset($hrefChunks[1]))
             $rightSide = trim($hrefChunks[1]);

           // left side of URL comes from manifest hash, not as a basename,
           // with right side of # placemarker stuff coming from the nav element
           // grep -H actionitem *php
           //

             // need to get the li element text value for basenameHref
           $basenameHref = $childHref;
           if (!strstr($childHref, '#'))
             $basenameHref .= $rightSide;

           // not the childHref you want the value from the manifest hash
           $href = $this->pathKludge . '/' . $hashedHref . '#' . $rightSide;
           $url = '?robopage=' . $_SESSION['currentDirUrl'] . '&subPath=' . $href;

           //$ret .= '<li><a href="'.$url.'">' . $basenameHref . '</a></li>';
           $ret .= '<li><a href="' . $url . '">' . $child . '</a></li>';
         }
         else
         {
           echo "error on basename2UrlHash[$hrefKey] and/or $childHref <br/>";
         }
       } else if ($child->getName() == 'ol')
       {
         $ret .= $this->processOl($child);
       }
     }
     $ret .= '</li>';
     return $ret;
   }

   function processOl($olNode)
   {
     $ret = '<ol style="list-style-type: none;">';
     //$ret = '<ol>';
     foreach ($olNode->children() as $child)
     {
       $ret .= $this->processLi($child);
     }

     $ret .= '</ol>';

     return ($ret);
   }

   function getOutput()
   {
     $ret = '<div id="toc">';
     $ret .= '<h3> Table of Contents </h3>';
     $xml = simplexml_load_file($this->tocFilePath);
     $xml->registerXPathNamespace('x', 'http://www.w3.org/1999/xhtml');

     foreach ($xml->xpath('//x:nav/x:ol') as $olNode)
     {
       $ret .= $this->processOl($olNode);
     }
     return $ret . '</div>';
   }

   function mkPageNodesHash()
   {
     $xml = simplexml_load_file($this->opfFilePath);
     $xml->registerXPathNamespace('x', 'http://www.idpf.org/2007/opf');

     $href = $lastHref = $lastlastHref = '';
     $idref = $lastidref = $lastlastiderf = '';
     foreach ($xml->xpath('//x:spine/x:itemref') as $itemrefElement)
     {
       $lastidref = $idref;
       $idref = (string) $itemrefElement['idref'];

       $lastlastHref = $lastHref;
       $lastHref = $href;

       $href = @$this->id2UrlHash[$lastidref];

       // get the node correstponding to the previous line in the spine
       $navigationNode = new navigationNode();

       $navigationNode->prevUrl = $lastlastHref;
       $navigationNode->thisUrl = $lastHref;
       $navigationNode->nextUrl = $href;

       //$navigationNode->dbg();


       $this->basename2NodeHash[$lastHref] = $navigationNode;
     }
   }

 }

?>
