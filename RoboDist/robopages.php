<?php

@session_start();

require_once("dirCrawler.class.php");
include_once("conf/globals.php"); /// zap this?
require_once("plugins/roboMimeTyper.php");
// ... move this into the ctor system?
$plugins = file("conf/plugins.ini");
$pcnt = count($plugins);
for ($i = 0; $i < $pcnt; $i++) {
    $plugin = trim($plugins[$i]) . '.php';
    include_once("plugins/$plugin");
}

class robopages extends dirCrawler {

    function __construct() {
        $this->mimer = new roboMimeTyper();
        $this->init();
        $this->readDefinitionFile();
    }

    function init() {
        global $sys_layout;

        staticRoboUtils::getpostClean();
        $this->determineLayout();

        if (isset($_GET['dbg']))
            $this->dbg = 1;

        //echo "robopages DOCUMENT_ROOT: ", $_SERVER['DOCUMENT_ROOT'], "<br/>"; // with /fragments/
        $_SESSION['prgrmDocRoot'] = getcwd() . '/fragments/';
        //echo "robo prgrmDocRoot: ", $_SESSION['prgrmDocRoot'], "<br/>";  // without /fragments/

        $_SESSION['prgrmUrlRoot'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', getcwd() . '/');
        //echo "robo prgrmUrlRoot: ", $_SESSION['prgrmUrlRoot'], "<br/><br/>";

        $this->setPathAndUrlParms();
    }

}
