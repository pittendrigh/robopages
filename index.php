<?php
require_once("domDrone.class.php");
require_once("staticDomDrone.class.php");
include_once("conf/globals.php");


global $sys_title, $sys_static_mode;
// perhaps you want a different layout for the home page
// ....if so edit layouts/main.xml 
// and use this
//if (!isset($_GET['layout']) && (!isset($_GET['robopage']) || $_GET['robopage'] == "index.php"))
//    $_GET['layout'] = 'blog';


if (isset($sys_title))
    $title = $sys_title; //sys_title comes from conf/globals.php
else
    $title = "Robopages";

$page = null;
if (isset($_SESSION['isLoggedIn']) && isset($_SESSION['privilege']) && $_SESSION['privilege'] == 'nimda')
{
    require_once("roboAdmin.php");
    //$page = new roboAdmin();
    $page = new domDrone();
}
else
{
    $page = new domDrone();
}
echo $page->startHTML(FALSE);
echo $page->printDivs();
echo StaticRoboUtils::endHTML();

if (isset($sys_static_mode) && $sys_static_mode == TRUE)
{
    $spage = new staticDomDrone();
    $spage->EchoStatic($page->startHTML(TRUE), "w");
    $spage->staticDrone();
    $spage->EchoStatic(StaticRoboUtils::endHTML(), "a");
}
?>
