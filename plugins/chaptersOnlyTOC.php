<?php

@session_start();
include_once("plugin.php");
include_once("roboMimeTyper.php");
include_once("p2nHandler.php");

class chaptersOnlyTOC extends plugin
{
  public   $mimer;
  public   $p3nHandler;
  
  function _construct()
  {
  $this->init();
  }
  
  // ...pass in a reference to an existing p2nHandler?
  function init()
  {
  $this->mimer = new roboMimeTyper();
  $this->p2nHandler = new p2nHandler();
  }
  
  // action starts here
  function getOutput($divid)
  {
  $ret = '';
  
  // global chapter links are the top level directories 
  // plus any *.htm files (*.htms with no path slashes)
  //
  $cnt = count($this->p2nHandler->globalChapterLinks);

  for($i=0; $i<$cnt; $i++)
  {
  $ret .= $this->p2nHandler->globalChapterLinks[$i];
  }
  
  return($ret);
  }

}

?>
