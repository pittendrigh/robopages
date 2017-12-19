<?php

@session_start();
include_once("plugin.php");

class mkGoogle extends plugin {

    function __construct() {
        
    }

    function getOutput($divid) {
        $ret = '';

        $ret .= <<<END
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- phoney -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-3590943253305524"
     data-ad-slot="7628890556"
     data-ad-format="auto"></ins>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
END;

        return ($ret);
    }

}

?>
