<?php

@session_start();

include_once("mkBreadcrumbs.php");
include_once("lazyLoadSlideshow.php");
include_once("dynamicNavigation.php");

class mobiusSlides extends lazyLoadSlideshow {


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
