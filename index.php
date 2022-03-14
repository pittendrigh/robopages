<?php
require_once("domDrone.class.php");
require_once("staticDomDrone.class.php");
include_once("conf/globals.php");

global $sys_static_mode;

   $layout=null;
   if(!isset($_GET['layout']) && (!isset($_GET['robopage'])
     || $_GET['robopage'] == "index.php")){
        $layout='main';

        // the following, for now, so you can switch from one roboBook
        // to another without problems. $_SESSION variable name-spacing 
        // has not happened yet. 
        session_unset();
   }


$page = new domDrone($layout);

if (isset($sys_static_mode) && $sys_static_mode == TRUE)
{
    $spage = new staticDomDrone();
    $spage->EchoStatic($page->startHTML(TRUE), "w");
    $spage->staticDrone();
    $spage->EchoStatic(StaticRoboUtils::endHTML(), "a");
} else {
   echo $page->startHTML(FALSE);
   echo $page->printDivs();
   echo StaticRoboUtils::endHTML();
}

?>
