<?php

 @session_start();

 include_once("flexyFileContent.php");
 include_once("bookTOC.php");

 class book extends flexyFileContent
 {

   public $bookTOC;

   public function construct()
   {

     $this->init();
   }

   // epub3 for now
   function init()
   {
     
   }

   function mkNextPrevPageButtons()
   {
     $ret = $queryPath = '';
     if (isset($_GET['subPath']))
       $queryPath = $_GET['subPath'];
     else
     {
       // ??? grep -H actionitem *php 
       $queryPath = $_SESSION['currentDisplay'];
     }
     $pageKey = basename($queryPath);
     //echo "mkNext working with pageKey ", $pageKey, "<br/>";
     $navigationNode = @$this->bookTOC->basename2NodeHash[$pageKey];
     if (isset($navigationNode) && $navigationNode != null)
     {
       //$navigationNode->dbg();

       $prevHref = $navigationNode->prevUrl;
       $nextHref = $navigationNode->nextUrl;
       $gotoHref = '';
       if (isset($_POST['nextpage']))
         $gotoHref = $nextHref;
       else
         $gotoHref = $prevHref;

//echo "prevHref: ", $prevHref, "<br/>";     
//echo "nextHref: ", $nextHref, "<br/>";     
       $nextUrl = '?robopage=' . $_SESSION['currentDirUrl'] . '&amp;subPath=' . $_SESSION['pathKludge'] . '/' . $nextHref;
       $prevUrl = '?robopage=' . $_SESSION['currentDirUrl'] . '&amp;subPath=' . $_SESSION['pathKludge'] . '/' . $prevHref;

       $ret .= '<a href="' . $prevUrl . '"><input type="button" value="prev page"/></a>';
       $ret .= "&nbsp; &nbsp;" . '<a href="' . $nextUrl . '"><input type="button" value="next page"/></a>';
     }

     return ($ret);
   }

   function handleForm()
   {
     $pageKey = basename($_GET['subPath']);
     $navigationNode = $this->bookTOC->basename2NodeHash[$pageKey];
     $prevHref = $navigationNode->prevUrl;
     $nextHref = $navigationNode->nextUrl;
     $gotoHref = '';
     if (isset($_POST['nextpage']))
       $gotoHref = $nextHref;
     else
       $gotoHref = $prefHref;

     // header? .......... cant.  Output already rendered
   }

   function getOutput($optional = null)
   {
     $ret = '';


     //echo $_SESSION['currentDirPath'] . 'META-INF' . "<br/>"; 
     if (@stat($_SESSION['currentDirPath'] . 'META-INF'))
     {
       //echo __LINE__, "<br/>";
       $this->bookTOC = new bookTOC();
       $currentIncomingUrl = null;

       $query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
       parse_str($query, $parms);

       $incomingGETPageParm = $parms['robopage'];

       if (substr($incomingGETPageParm, -1) == '/')
         $incomingGETPageParm = substr($incomingGETPageParm, 0, strlen($incomingGETPageParm) - 1);
       $incomingHref = '?robopage=' . $incomingGETPageParm;

       // if it's a directory tack on $_SESSION['currentDisplay']
       if (!strstr(basename($incomingGETPageParm), '.'))
         $incomingHref .= '/' . $_SESSION['currentDisplay'];

       //$ret .= $this->mkNextPrevPageButtons();
       $ret .= $this->bookTOC->getOutput();
       //$ret .= $this->mkNextPrevPageButtons();
     }


     $_SESSION['currentDisplay'] = $_GET['subPath'];
     $ret .= parent::getOutput('');



     return($ret);
   }

 }

?>
