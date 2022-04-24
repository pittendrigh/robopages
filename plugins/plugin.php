<?php
@session_start();

include_once("plugin.interface.php");

class plugin implements pluginInterface
{
    protected $selfUrl;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->selfUrl = $this->determineSelfUrl();
    }

    public function determineSelfUrl()
    {
        $ret = preg_replace('://[\/]*:', '/', '?robopage=' . $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']);
        $this->selfUrl = $ret;
        return($ret);
    }

    public function getSelfUrl()
    {
        return $this->selfUrl;
    }

    public function getOutput($divid)
    {
        $ret = '';

        return($ret);
    }
}
?>
