<?php
@session_start();

class editor extends plugin
{
    protected $mode;
    
    function __construct($mode=null) {
    //$this->checkAuthorityCredentials();
    if(isset($mode))
       $this->mode = $mode;
    $this->init();
    }
    

    function saveNewlines($str)
    {
        $ret = stripslashes($str);
        //$ret = preg_replace("/\\\\n/","xxxYyYZzZ",$ret);
        //$ret = str_replace("xxxYyYZzZ",'\n',$ret);
        $ret = preg_replace("/\\\\n/", dechex(187), $ret);
        $ret = str_replace(dechex(187), '\n', $ret);
        $ret = htmlentities($ret);
        return $ret;
    }

    function showEditForm()
    {
        $file = $ret = '';

        if (!isset($_POST['filename']))
        {
            $ret = <<<ENDO
         <button><a href="?layout=nerd">Choose a file to edit first</a></button> 
ENDO;
            return $ret;
        }

        if (isset($_GET['robopage']) && $_GET['robopage'] != '')
            $url = '?robopage=' . $_GET['robopage'] . "&amp;layout=editor";
        else
            $url = '?layout=editor';

        $ret .= "\n" . '<form method="post" action="' . $url . '" method="post">' . "\n";

        ///$file = preg_replace("/\s/", "_", $_SESSION['currentDirPath'] . $_POST['filename']);
        $file = $contents = '';
        if (isset($_POST['filename']))
        {
            $file = $_SESSION['currentDirPath'] . $_POST['filename'];
            $contents = @file_get_contents($file);
            $contents = $this->saveNewLines($contents);
        }
        //else{ StaticRoboUtils::dbgSession(); }
        $ret .= '<h4> Editing: ' . $file . '</h4>';

        $js1 = '<script type="text/javascript" src="js/jquery.min.js"></script>' . "\n";
        $js2 = '<script type="text/javascript" src="js/tinymce.min.js"></script>'. "\n";
        $js3 = '<script type="text/javascript" src="js/init-tinymce.js"></script>'."\n";

        $ret .= $js1 . $js2 . $js3;
        $ret .= '<textarea name="contents" class="tinymce">';

        $ret .= $contents;
        $ret .= '</textarea>';
        $ret .= '<input type="hidden" name="mode" value="SaveFile">';

        //if(isset($_POST['filename'])) 
        //   $ret .= '<input type="hidden" name="filename" value="' . $_POST['filename'] . '">';
        $ret .= '<input type="hidden" name="filename" value="' . $file . '">';

        $ret .= '
   <br>
   <input type="submit" mode="save" name="save" value="Save File" >' . "\n" . '
   <a href="?layout=nerd"> cancel </a>' . "\n" . '
</form>' . "\n";
        return $ret;
    }

    function SaveFile()
    {
/*
       if($this->mode == 'blog')
           $file = $_SESSION['prgrmDocRoot'] . 'roboresources/Blog/' . date("Y-m-d:h:i:s") . '.blog';
        else   
           $file = $_SESSION['currentDirPath'] . basename($_POST['filename']);
*/

        $backFile = $_SESSION['prgrmDocRoot'] . 'roboresources/BAKS/';  . basename($_POST['filename']);
        $backContents = @file_get_contents($backFile);
        $fp = fopen($backFile, "w");
        fwrite($fp, $backContents);
        fclose($fp);

        $contents = $_POST['contents'];
        $contents = html_entity_decode($contents);
        $fp = fopen($file, "w");
        fwrite($fp, $contents);
        fclose($fp);
    }

    function getOutput($divid)
    {
        $privilege = StaticRoboUtils::checkAuthorityCredentials();

        if (!$privilege)
        {
            $ret = <<<ENDO
         <button><a href="?layout=auth">Login First</a></button> 
ENDO;
            return $ret;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['mode'] == 'SaveFile')
        {
            $this->SaveFile();
            $robopage = $_GET['robopage'];

            $ret = <<<ENDO
        <a href="?robopage=$robopage&amp;layout=nerd"><button>Saved! -- back to the admin screen</button></a>
ENDO;
        }
        else
            $ret = $this->showEditForm();
        return $ret;
    }

}
