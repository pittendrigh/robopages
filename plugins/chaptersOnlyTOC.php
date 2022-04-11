<?php

@session_start();
include_once("plugins/plugin.php");
include_once("plugins/roboMimeTyper.php");
include_once("plugins/p2nHandler.php");

class chaptersOnlyTOC extends plugin
{
  public   $mimer;
  public   $p2nHandler;
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
      $state = 'toc';

      if (isset($_COOKIE['buttonState']) 
          && in_array($_COOKIE['buttonState'], ['toc', 'TOC'])) {
        $state = $_COOKIE['buttonState'];
      } 

      $ret .= "<button id=\"tocPopper\" 
                 onClick=\"flipAndRedraw();\">toc</button>";
      $ret .= $this->nextPrevButtons->getOutput('');
      $ret .= '<div id="tocComesAndGoes">';

      // why the following hack? To push links visually down?
      // comparet to same output from mainTOC.php
      // It does not make sense
      $ret .= ' <p class="clearboth"> &nbsp; </p>';

  // global chapter links are the top level directories 
  // plus any *.htm files (*.htms with no path slashes)
  $cnt = count($this->p2nHandler->globalChapterLinks);
  for($i=0; $i<$cnt; $i++)
  {
     $ret .= $this->p2nHandler->globalChapterLinks[$i];
  }
  $ret .= '<script> fixItUp("' . $state . '");</script>';
  
  $ret .= '</div>'; 
  return($ret);
  }
}

?>
