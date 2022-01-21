<?php

@session_start();
include_once("plugin.php");
include_once("nextPrevButtons.php");
include_once("roboMimeTyper.php");
include_once("p2nHandler.php");

class globalTOC extends plugin {

    protected $nextPrevButtons;
    protected $mimer;
    public $p2nHandler;

    function _construct() {
        $this->init();
    }

    function init() {
        $this->p2nHandler = new p2nHandler();
        $this->mimer = new roboMimeTyper();
        $this->nextPrevButtons = new nextPrevButtons();
    }

    function getTOCJs() {
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
    function getOutput($divid) {

        $nextPrevButtons = new nextPrevButtons();

        $ret = $top = $bottom = '';
        $top .= '<button id="tcdo" onClick="tocToggle()">toc</button>';
        $top .= $this->getTOCJs();
        $top .= '<div id="ttoc">';

        $top  .= '<div class="buttonbox">'.$nextPrevButtons->getOutput('')."</div>";
        //echo htmlentities($mess);
        $ret .= $top;

        // global chapter links are the top level directories plus any *.htm files, with no path slashes
        $cnt = count($this->p2nHandler->globalChapterLinks);
        //echo "cnt globals: " , $cnt, "<br/>";
        for ($i = 0; $i < $cnt; $i++) {
            $top .= $this->p2nHandler->globalChapterLinks[$i];
        }

// if NOT in the Books top chapter directory then we are in a chapter
// if so we want to display, at bottom, all available page links in that chapter
// local stuff needs to be factored out, so it can be included
// or not by the xml, perhaps to be used by a G a l l e r y
// globalTOC and localTOCo 
//
        $this->p2nHandler->find_additional_pages();
        if (!$this->p2nHandler->inBookTopDir()) 
        {
            $bottom .= '<div id="roboBookBottom"><hr/>';
            $localLinksArray = $this->p2nHandler->getLocalPageLinks();
            $cnt = count($localLinksArray);
            $bottom .= '<h3 class="roboBookThisChapter"> -- ' . $this->p2nHandler->getThisChapter() . " -- </h3>";

            foreach (array_keys($localLinksArray) as $akey) {
                $link = $localLinksArray[$akey];
                $bottom .= $link;
            }
            foreach (array_keys($this->p2nHandler->additionalLinksHash) as $akey) 
            {
            //    echo "additional akey: ", $akey, "<br/>";

                $link = $this->p2nHandler->additionalLinksHash[$akey];
                $bottom .= $link;
            }

            $bottom .= '</div>';
        }


        $ret = $top . $bottom . '</div>';
        return($ret);
    }

}

?>
