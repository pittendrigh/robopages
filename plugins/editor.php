<?php

include_once("adminPlugin.interface.php");
include_once("adminPlugin.php");

class editor extends adminPlugin implements adminPluginInterface
{
    protected $mode;
    
      function __construct($mode=null) 
      {

       
       $this->mode=$mode;

      $this->init();
      }
     

    function saveNewlines($str)
    {
        $ret = $str;
        //$ret = preg_replace("/\\\\n/", dechex(187), $ret);
        //$ret = stripslashes($str);
        //$ret = str_replace(dechex(187), '\n', $ret);
        $ret = htmlentities($ret);
        //echo "saveNewLines: ", $ret, "<br/>";
        return $ret;
    }

    function showEditForm()
    {
        $file = $ret = '';

        if (  !isset($_POST['filename']) && $this->mode != 'blog')
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

        $file = $contents = '';
        if (isset($_POST['filename']))
        {
            $file = $_SESSION['currentDirPath'] . $_POST['filename'];
            $contents = @file_get_contents($file);
            //$contents = $this->saveNewLines($contents);
            //$contents = htmlentities($contents);
        } else {
            $file = $_SESSION['prgrmDocRoot'] . 'roboresources/Blog/' . date("Y-m-d:h:i:s") . '.blog';
        }
        $ret .= '<h4> Editing: ' . $file . '</h4>';

        $js1 = '<script type="text/javascript" src="js/jquery.min.js"></script>' . "\n";
        $js2 = '<script type="text/javascript" src="js/tinymce.min.js"></script>'. "\n";
        $js3 = '<script type="text/javascript" src="js/init-tinymce.js"></script>'."\n";

        $ret .= $js1 . $js2 . $js3;
        $ret .= '<textarea name="contents" class="tinymce">';
        $ret .= $contents;
        $ret .= '</textarea>';
        $ret .= '<input type="hidden" name="mode" value="SaveFile">';
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

       $file = $_POST['filename'];
       $contents = $_POST['contents'];
       $backFile = $_SESSION['prgrmDocRoot'] . 'roboresources/BAKS/' . basename($_POST['filename']);

        $fp = fopen($backFile, "w");
        fwrite($fp, $contents);
        fclose($fp);

        $contents = html_entity_decode($contents);
        $fp = fopen($file, "w");
        fwrite($fp, $contents);
        fclose($fp);
    }

    function getOutput($divid)
    {
        $privilege = StaticRoboUtils::isAdmin();

        if (!$privilege)
        {
            $ret = <<<ENDO
         <button><a href="?layout=authUtils">Login First</a></button> 
ENDO;
            return $ret;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['mode'] == 'SaveFile')
        {
           
            $this->SaveFile();
            $robopage='';
            if(isset($_GET['robopage']))
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
