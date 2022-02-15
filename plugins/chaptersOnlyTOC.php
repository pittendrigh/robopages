<?php

@session_start();
include_once("plugin.php");
include_once("roboMimeTyper.php");
include_once("p2nHandler.php");

class chaptersOnlyTOC extends plugin
{
  public   $mimer;
  public   $p3nHandler;
  public   $nextPrevButtons;
  
  function _construct()
  {
  $this->init();
  }
  
  // ...pass in a reference to an existing p2nHandler?
  function init()
  {
  $this->mimer = new roboMimeTyper();
  $this->p2nHandler = new p2nHandler();
  $this->nextPrevButtons = new nextPrevButtons();
  }
  
  // action starts here
  function getOutput($divid)
  {
  $ret = '';
      $homeLabel = '';
      global $sys_home_link_label;
      if(isset($sys_home_link_label) && $sys_home_link_label != '')
      {
          $homeLabel = $sys_home_link_label;
          $ret .= '<h3><a href="' . $_SESSION['prgrmUrlRoot'] . '">'.$homeLabel.'</a></h3>';
      }

      $ret .= "<button id=\"tcdo\" onClick=\"flipAndRedraw();\">toc</button>";
      $ret .= $this->nextPrevButtons->getOutput('');
      $ret .= '<div id="ttoc">';

      $state = 'toc';
      if (!isset($_COOKIE['buttonState']))
      {
        $_COOKIE['buttonState'] = $state;
      }
  
      if (isset($_COOKIE['buttonState']) && in_array($_COOKIE['buttonState'], ['toc', 'TOC']))
      {
        $state = $_COOKIE['buttonState'];
      }
 


  
  // global chapter links are the top level directories 
  // plus any *.htm files (*.htms with no path slashes)
  $cnt = count($this->p2nHandler->globalChapterLinks);
  for($i=0; $i<$cnt; $i++)
  {
  $ret .= $this->p2nHandler->globalChapterLinks[$i];
  }
 
  $ret .= '</div><script> fixItUp("' . $state . '");</script>'; 
  return($ret);
  }

}

?>
