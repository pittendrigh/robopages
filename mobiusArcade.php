<?php

@session_start();

include_once("mkBreadcrumbs.php");
include_once("arcade.php");
include_once("dynamicNavigation.php");

class mobiusArcade extends Arcade {


    public function construct() {
        $this->init();
    }

    public function getOutput($divid) {
        $ret = '';
        $dynNo = new dynamicNavigation();
        $ret .= '<div id="main-content">';
                  $ret .= parent::getOutput('goo');
        $ret .= '</div>';
        $ret .= $dynNo->getOutput('nav');

        return($ret);
    }

}

?>
