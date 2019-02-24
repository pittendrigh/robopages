<?php

@session_start();
include_once("plugin.php");
include_once("processBackTics.class.php");

class meme extends plugin {

    var $img;
    var $text;
    var $link;

    function __construct() {
        
    }

    
    function mkImage()
    {
         $ret = '';

         $imgSrc = $this->img;
         $ret .= '<img class="imgMeme" src="' . $imgSrc . '" alt="'. basename($imgSrc) . '"/>';
          return $ret;
    }

    function getOutput($divid) {

        $data=$ret=$meme='';
        $chunks=array();

        $lclSrc = $_SESSION['currentDirPath'] . 'roboresources/' . $divid . '.meme';
        $defaultSrc = $_SESSION['prgrmDocRoot'] . 'roboresources/' . $divid . '.meme';

        if (@stat($lclSrc)) {
            $data = file_get_contents($lclSrc);
        } else if (@stat($defaultSrc)) {
            $data  = file_get_contents($defaultSrc);
        }


        $data = preg_replace("/\n/","", $data); 
        $chunks = explode("||", $data);
  
        $this->link=null;
        $this->img = $chunks[0];
        $this->text = $chunks[1];
        if(isset($chunks[2]) && $chunks[2] != null)
          $this->link = $chunks[2];

        $meme = $this->MkImage();
        $meme .= '<p style="clear:both;">'.$this->text. "</p>";

        if(isset($this->link) && $this->link != null)
        {
           $ret .= '<a href="' . $this->link . '">' . $meme . '</a>';
        } else {
           $ret = $meme;
        }

/*
if(strstr($this->text,"erac"))
{
        echo htmlentities($ret);
        exit;
}
*/
        return ($ret);
    }
}

?>
