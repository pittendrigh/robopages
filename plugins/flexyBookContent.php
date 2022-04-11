<?php @session_start();

include_once("plugins/plugin.php");
include_once("plugins/flexyFileContent.php");

class flexyBookContent extends flexyFileContent 
{
// ouch. Need default page from nextPrevButtons.php
// instantiate them first and set a $_SESSION['something']    
// or have nextPrevButtons change $_SESSION['currentDisplay']
// or what?
//

function getTentativeDisplayFile()
    {
      $ret = '';
      $ret = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];

      return($ret);
    }




}
?>
