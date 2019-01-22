<?php
include_once("adminPlugin.interface.php");
include_once("plugin.php");

class adminPlugin extends plugin implements adminPluginInterface
{

    //protected $administrableDivID;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        parent::init();
        //$this->administrableDivID = StaticRoboUtils::determineAdministrableDivID();
        $this->checkAuthorityCredentials();
    }

    public function checkAuthorityCredentials()
    {
        if (!isset($_SESSION['privilege']) || ($_SESSION['privilege'] != 'nimda'))
        {
            $ret = <<<ENDO
<button><a href="?layout=auth">Login First </button>
ENDO;
        }
    }

}
?>
