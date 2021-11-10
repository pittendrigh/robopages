<?php
@session_start();

include_once("plugin.interface.php");

class AdminPlugin extends plugin implements adminPluginInterface
{

    public function getSecureOutput($divid)
    {
        $ret = '';
        return $ret;
    }

    public function getOutput($divid)
    {
        //echo "adminPlugin testing... <br/>";
        $ret = '';
        if(StaticRoboUtils::isAdmin())
        {
          $ret = $this->getSecureOutput($divid);
        } else {
           $currentDirUrl = $_SESSION['currentDirUrl'];
           $ret = <<<ENDO
<a class="button" href="?robopage=$currentDirUrl&amp;layout=authUtils">Login First</a>
ENDO;
        }
        return($ret);
    }

}
?>
