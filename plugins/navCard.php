<?php

@session_start();
include_once("plugin.php");
include_once("Link.php");
include_once("StaticRoboUtils.php");

class navCard extends plugin {

    var $body;
    var $label;

    function __construct($href, $body,$label) {
       $this->href=$href;
       $this->body=$body; 
       $this->label=$label; 
    }

    function getOutput($divid) {
        $ret = '';
$href = $this->href;
$body = $this->body;
$label = $this->label;

$ret .= <<<ENDO
<a class="navCardLink" href="$href">
<div class="navCard">
  <div class="navCardbody">
      $body
  </div>
  <div class="navCardcaption">
       $label
  </div>
</div>
</a>
ENDO;


        return ($ret);
    }
}

?>
