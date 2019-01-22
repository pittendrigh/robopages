<?php
include_once("plugin.interface.php");

interface adminPluginInterface extends pluginInterface
{

    public function checkAuthorityCredentials();
}
?>
