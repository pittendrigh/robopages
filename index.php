<?php

require_once("dirCrawler.class.php");
require_once("robopages.php");
include_once("conf/globals.php");


// perhaps you want a different layout for the home page
// ....if so edit layouts/main.xml 
// pafasdfasf
/*
  if(!isset($_GET['layout']) && (!isset($_GET['page'])
  || $_GET['page'] == "index.php"))
  $_GET['layout'] = 'main';
 */


global $sys_title;
if (isset($sys_title))
    $title = $sys_title; //sys_title comes from conf/globals.php
else
    $title = "Robopages";


$page = null;
$page = new robopages();

echo $page->startHTML($title);
echo $page->crawl();
echo staticRoboUtils::endHTML();
?>
