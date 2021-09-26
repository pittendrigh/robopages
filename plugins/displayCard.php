<?php

@session_start();
include_once("plugin.php");
include_once("Link.php");
include_once("StaticRoboUtils.php");

class displayCard extends plugin {

    var $banner;
    var $body;
    var $caption;

    function __construct($banner,$body,$caption=null) {
       $this->banner=$banner; 
       $this->body=$body; 
       $this->caption=$caption; 
    }

    function getOutput($divid) {
        $ret = '';
$ret .= <<<ENDO
<div class="displaycard">
  <div class="cardtitle">
       $this->banner
  </div>
  <div class="cardbody">
      $this->body
  </div>
</div>
ENDO;

  if($this->caption != null)
$ret .= <<<cENDO
  <div class="cardcaption">
       $this->caption
  </div>
cENDO;

        return ($ret);
    }
}

?>
