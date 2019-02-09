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

        $robopage = $_SESSION['currentDirUrl'];
        $this->dir_dest = $_SESSION['currentDirPath'];
        $this->dir_pics = $_SESSION['currentDirPath'] . 'roboresources/';

        $this->action = (isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : ''));

        if ($this->action == 'simple')
        {

            // ---------- SIMPLE UPLOAD ----------
            // we create an instance of the class, giving as argument the PHP object
            // corresponding to the file field from the form
            // All the uploads are accessible from the PHP object $_FILES
            $handle = new upload($_FILES['my_field']);

            // then seeif the file has been uploaded properly
            // in its *temporary* location in the server (often, it is /tmp)
            if ($handle->uploaded)
            {
                // yes, the file is on the server
                // now, we start the upload 'process'. That is, to copy the uploaded file
                // from its temporary location to the wanted location
                // It could be something like $handle->process('/home/www/my_uploads/');
                $handle->process($this->dir_dest);

                // we seek if everything went OK
                if ($handle->processed)
                {
                    // everything was fine !
                    $ret .= '<p class="result">';
                    $ret .= '<a style="padding: 0.5em; display: block; width: 12em;" href="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd"> Back to the Admin Screen</a><br/><br/>';
                    $ret .= '  [' . $handle->file_dst_name . '] uploaded to <b>' . $_SESSION['currentDirPath'] . '</b><br />';
                    //$ret .= '  File: <a href="' . $this->dir_pics . '/' . $handle->file_dst_name . '">' . $handle->file_dst_name . '</a>';
                    $ret .= '   (' . round(filesize($handle->file_dst_pathname) / 256) / 4 . 'KB)';
                    $ret .= '</p>';
                }
                else
                {
                    // one error occured
                    $ret .= '<p class="result">';
                    $ret .= '  <b>File not uploaded to the wanted location</b><br />';
                    $ret .= '  Error: ' . $handle->error . '';
                    $ret .= '</p>';
                }
                // we delete the temporary files
                $handle->clean();
            }
            else
            {
                // if we're here, the upload file failed for some reasons
                // i.e. the server didn't receive the file
                $ret .= '<p class="result">';
                $ret .= '  <b>File not uploaded on the server</b><br />';
                $ret .= '  Error: ' . $handle->error . '';
                $ret .= '</p>';
            }
            $ret .= "<br/><br/><hr><br/>" . $handle->log . '<br />';
        }
        else
        {
            $ret = <<<ENDO
    <fieldset>
        <legend> Robo uploader</legend>
        <p>Pick up a file to upload, and press "upload" </p>
        <form name="form1" enctype="multipart/form-data" method="post" action="?robopage=$robopage&amp;layout=upload" />
            <p><input type="file" size="32" name="my_field" value="" /></p>
            <p class="button"><input type="hidden" name="action" value="simple" />
            <input type="submit" name="Submit" value="upload" /></p>
        </form>
ENDO;
        }
        //$robopage = $_SESSION['currentDirUrl'];
        $ret .= '<a href="?robopage=' . $robopage . '&amp;layout=nerd">Cancel '.$robopage.'</a>';
        $ret .= '</fieldset>';
        return $ret;
    }

}
?>

