<?php
include_once("plugins/plugin.php");

class pluginnotfound extends plugin
{

    function assembleContent($plugin, $divid)
    {
        return $this->getOutput($plugin);
    }

    function getOutput($plugin)
    {
        return ".....<b>plugins/$plugin</b> nnnnot found<br/>";
    }

}
