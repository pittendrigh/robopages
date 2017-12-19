<?php

require_once("dirCrawler.class.php");
require_once("robopages.php");
include_once("conf/globals.php");


// perhaps you want a different layout for the home page
// ....if so edit layouts/main.xml 
// and use this
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
if (isset($_SESSION['isLoggedIn']) && isset($_SESSION['privilege']) && $_SESSION['privilege'] == 'admin') {
    require_once("roboAdmin.php");
    $page = new roboAdmin();
} else {
    if ( (isset($_GET['page']) && strstr($_GET['page'], "-online-plans")) ) {
        if ((!isset($_SESSION['to_the_plans']) || !isset($_SESSION['mrb'])) || ($_SESSION['to_the_plans'] != 1 && $_SESSION['mrb'] != "milkandhoney")) {
            echo "userlogin<br/>";
            $_GET['page'] = null;
            $_GET['layout'] = "userlogin";
        }
    }

    $page = new robopages();
}
echo $page->startHTML($title);
echo $page->crawl();
echo staticRoboUtils::endHTML();
?>
