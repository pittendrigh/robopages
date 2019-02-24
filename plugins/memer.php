<?php

include_once("meme.php");

class memer
{

 var $meme;

 function __construct($divid)
 {
   $this->meme = new meme($divid);

   echo "\n" . '<div class="meme">'. $this->meme->getOutput($divid) . '</div>' . "\n";
 } 
  

}

?>
