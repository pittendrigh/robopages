<?php
require_once("domDrone.class.php");
require_once("staticDomDrone.class.php");
include_once("conf/globals.php");

global $sys_static_mode;

$page = new domDrone();


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
