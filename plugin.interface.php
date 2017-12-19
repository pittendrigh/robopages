<?php

interface pluginInterface {

    public function init();

    public function determineSelfUrl();

    public function getSelfUrl();

    public function assembleContent($plugin, $divid);

    public function getOutput($divid);
}

?>
