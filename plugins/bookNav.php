<?php
@session_start();

include_once("plugin.php");
include_once("nextPrevPage.php");

class bookNav extends plugin 
{
    public function __construct()
    {
        $this->init();
    }

    public function notInTopLevelChapter()
    {
      $ret = TRUE;

      $leftPath = str_replace($_SESSION['prgrmDocRoot'], '', dirname($_SESSION['p2nFile'])) . '/';
      $rightPath = $_SESSION['currentDirUrl'];

      // better get $_SESSION['p2nFile'] set properly!
      //echo $leftPath . ' || ' . $rightPath . " <br/>";
      if($leftPath == $rightPath)
          $ret = FALSE;
      return $ret;
    }

    public function getOutput($divid)
    {
        $ret = '';

        $buttons = new nextPrevPage();
        $ret .= $buttons->getOutput('');
        $chaptersFragger = new file();
        $ret .= $chaptersFragger->getOutput('chaptersLinksList');
        $ret .= '<p/>';

        if($this->notInTopLevelChapter())
        {
           $toc = new dynamicNavigation();
           $ret .= $toc->getOutput('');
        }
      
        return($ret);
    }
}
?>
