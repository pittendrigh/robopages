<?php
error_reporting(E_ALL);
include_once("plugins/class.upload.php");
include_once("plugins/plugin.php");

class uploader extends plugin
{
    protected $dir_dest;  // = (isset($_GET['dir']) ? $_GET['dir'] : 'tmp');
    protected $dir_pics;   // = (isset($_GET['pics']) ? $_GET['pics'] : $dir_dest);
    protected $action;

    function getOutput($divid)
    {
        $ret = '';

        $ret = <<<ENDO
    <fieldset>
        <legend>Uploader</legend>
        <p>Pick a file to upload, and press "upload" </p>
        <form name="simpleform" enctype="multipart/form-data" method="post" action="?layout=nerd" />
            <p><input type="file" size="32" name="my_field" value="" /></p>
            <p class="button"><input type="hidden" name="action" value="simple" />
            <input type="hidden" name="mode" value="handleUpload" />
            <input type="submit" name="Submit" value="upload" /></p>
        </form>
    </fieldset>
ENDO;
        $robopage = $_GET['robopage'];
        $ret .= '<a href="?robopage=' . $robopage . '&amp;layout=nerd">Cancel</a>';
        $ret .= '</fieldset>';
        return $ret;
    }

}
?>

