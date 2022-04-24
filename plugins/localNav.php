<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("dynamicNavigation.php");

class localNav extends dynamicNavigation
{
  

  function getOutput($divid)
  {
    $ret = '';

    if($_SESSION['prgrmDocRoot'] == $_SESSION['currentDirPath']){
      return($ret);
    } else {
      $ret .= '<h3 class="highlighted">' . basename($_GET['robopage']) . '</h3>';
      $ret .= parent::getOutput($divid);
      return ($ret);
    }
  }


}
