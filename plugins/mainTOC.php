<?php

  @session_start();
  include_once("plugin.php");
  include_once("nextPrevButtons.php");
  include_once("roboMimeTyper.php");
  include_once("p2nHandler.php");

  class mainTOC extends plugin
  {

    protected $nextPrevButtons;
    protected $mimer;
    protected $p2nHandler;

    function _construct()
    {
      $this->init();
    }

    function init()
    {
      $this->mimer = new roboMimeTyper();
      $this->nextPrevButtons = new nextPrevButtons();
      $this->p2nHandler = new p2nHandler();
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

  if (x.style.display == "none")
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

    function getOutput($divid)
    {
      //static $oops = 0;
      //echo "getOutput [",$oops," ]<br/>";
      //$oops++;
      $ret = $top = $bottom = '';
      //echo 'globalTOC<br/>';
      $top .= $this->getTOCJs();
      $top .= '<button id="tcdo" onClick="tocToggle();">toc</button>';
      $top .= '<div id="ttoc">';
      $top .= $this->nextPrevButtons->getOutput('');

// global chapter links are the top level directories plus any *.htm files, with no path slashes
      $cnt = count($this->p2nHandler->globalChapterLinks);
      for ($i = 0; $i < $cnt; $i++)
      {
        $top .= $this->p2nHandler->globalChapterLinks[$i];
      }

// if NOT in the Books top chapter directory then we are inside a chapter directory
// if so we want to display, at bottom, all available page links inside that chapter
//
      if (!$this->p2nHandler->inBookTopDir())
      {
        $bottom .= '<div id="roboBookBottom"><hr/>';
        $localLinksArray = $this->p2nHandler->getLocalPageLinks();
        $cnt = count($localLinksArray);
        $bottom .= '<h3 class="roboBookThisChapter"> -- ' . $this->p2nHandler->getThisChapter() . " -- </h3>";

        foreach (array_keys($localLinksArray) as $akey)
        {
          $link = $localLinksArray[$akey];
          $bottom .= $link;
        }
        $bottom .= '</div>';
      }

// Everything above came from the p2n file.  What about last minute page additions
// that might not be in the p2n file yet?
//
      foreach ($this->p2nHandler->additionalLinksHash as $alink)
      {
        $bottom .= $alink . "\n";
      }

      $ret = $top . $bottom . '</div>';
      return($ret);
    }

  }

?>
