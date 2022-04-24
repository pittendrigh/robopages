<?php

@session_start();
include_once("plugin.php");
include_once("Link.php");
include_once("StaticRoboUtils.php");

class navcard extends plugin {

    var $body;
    var $label;

/* body is a thumbnail, label is arbitrary, perhaps from href */
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
<a class="cardlink" href="$href">
<div class="navcard">
  <div class="cardbody">
      $body
  </div>
  <div class="cardcaption">
       $label
  </div>
</div>
</a>
ENDO;


        return ($ret);
    }
}

?>
