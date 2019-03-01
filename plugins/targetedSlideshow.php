<?php

@session_start();
include_once("roboMimeTyper.php");
include_once("conf/globals.php");
include_once("plugins/lazyLoadSlideshow.php");

class targetedSlideshow extends lazyLoadSlideShow  {


    function __construct($path=null) 
    {
        if(isset($path) && $path != null)
        {
             $this->slideShowPath = $path;
        }
    }

    function setup()
    {
        global $sys_interval;
        if (isset($sys_interval))
            $this->interval = $sys_interval;
        else if(!isset($this->interval))
            $this->interval = 1000;
        $this->slideShowUrl = str_replace($_SERVER['DOCUMENT_ROOT'],"",$this->slideShowPath);
        $this->getImageFilenames($this->slideShowPath);
    } 
}
