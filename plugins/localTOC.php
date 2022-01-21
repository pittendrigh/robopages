<?php

@session_start();
include_once("plugin.php");
include_once("nextPrevButtons.php");
include_once("roboMimeTyper.php");
include_once("p2nHandler.php");

class localTOC extends plugin
{
public   $nextPrevButtons;
//public   $p2nFileDir;
//public   $p2nFile;
public   $currentBookName;
public   $mimer;
public   $allP2NLinks;
public   $missedLinks;
public   $globalChapterLinks;
public   $p3nHandler;

function _construct()
{
$this->init();
}

// doesn't do much, sets P2NFile path
// nothing happens after init until getOutput('')
function init()
{
$this->mimer = new roboMimeTyper();
$this->p2nHandler = new p2nHandler();
$this->p2nHandler->setP2NFile();
$this->nextPrevButtons = new nextPrevButtons();
$this->missedLinks = array();
}


function getTOCJs()
{
$ret = '';
$ret .= <<<ENDO
<script>
function tocToggle()
{
var x = document.getElementById("ttoc");
var b = document.getElementById("tcdo");
if (x.style.display === "none")
{
x.style.display = "block";
b.innerHTML="toc";
}
else
{
x.style.display = "none";
b.innerHTML="TOC";
}
}
</script>
ENDO;

return $ret;
}

// action starts here
function getOutput($divid)
{
$ret = $top = $bottom = '';
$top .= '<button id="tcdo" onClick="tocToggle()">toc</button>';
$top .= $this->getTOCJs();
$top .= '<div id="ttoc">';

// global chapter links are the top level directories plus any *.htm files, with no path slashes
$this->p2nHandler->getGlobalChapterLinks();
$cnt = count($this->p2nHandler->globalChapterLinks);
for($i=0; $i<$cnt; $i++)
{
$top .= $this->p2nHandler->globalChapterLinks[$i];
}

 $bottom .= $this->nextPrevButtons->getOutput('');

$ret =  $top . $bottom . '</div>';
return($ret);
}
}

?>
