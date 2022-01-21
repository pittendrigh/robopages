<?php

include_once("plugin.php");
include_once("nextPrevButtons.php");

class nextPrevButtonsWrapper
//class nextPrevButtonsWrapper extends plugin
{

 var $buttons;

 function __construct()
 {
   $this->buttons = new nextPrevButtons('');
 } 


 function getOutput($null)
 {
   $ret = '';
   $ret .= "\n" . '<div class="nextPrevButtons">'. $this->buttons->getOutput('') . '</div>' . "\n";
   return ($ret);
 }
}
?>
