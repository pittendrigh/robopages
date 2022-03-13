<?php
require_once("domDrone.class.php");
require_once("staticDomDrone.class.php");
include_once("conf/globals.php");

global $sys_static_mode;

   $layout=null;
   if(!isset($_GET['layout']) && (!isset($_GET['robopage'])
     || $_GET['robopage'] == "index.php"))
        $layout='main';

if(isset($_GET['robopage']))
{
 if (strstr($_GET['robopage'],'Ebook') 
      || stristr($_GET['robopage'],'diagrams')  
      || stristr($_GET['robopage'],'buffalo-boat-online-plans')) 
   {
       if(!isset($_SESSION['to_the_plans'])  || $_SESSION['to_the_plans'] != 1
            || !isset($_SESSION['mrb'])  || $_SESSION['mrb'] != 'milkandhoney')
       {
                 $_GET['robopage'] = null;
                 $_GET['layout'] = "userlogin";
       }
 }
}

$page = new domDrone($layout);

$comparitor='';
if(isset($_GET['robopage']) && $_GET['robopage'] != '')
  $comparitor = $_GET['robopage'];
$freepages = array("FliesBook","index.htm","cover.htm","preface.htm","introduction.htm","In-the-beginning");


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
