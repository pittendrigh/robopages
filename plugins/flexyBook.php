<?php
@session_start();

include_once("plugin.php");
include_once("flexyFileContent.php");
include_once("displayCard.php");

class flexyBook extends flexyFileContent 
{

    function getCaption()
    {
        $ret = $caption = '';
        $base = StaticRoboUtils::stripSuffix($_SESSION['currentDisplay']);
        $capfile = $_SESSION['currentDirPath'] . '/roboresources/pics/' . $base . ".cap";
        $caption = @file_get_contents($capfile);
        if ($caption != null)
        {
           $ret = $caption;
        }
        return '<p class="clearboth" >' .  $ret. '</p>';
    }




    public function getOutput($divid)
    {
        $ret = $memeArea = '';
        $nextPrevButtons = new nextPrevButtons();
        $memeArea = parent::getOutput($divid);

        //$ret .= '<div class="Meme">' .$memeArea. '</div>';
        $ret .= $memeArea;


        $ret .= '<div id="bottomNav">';
        $ret .= $nextPrevButtons->getOutput('');
        $ret .= '</div>';


       
        return($ret);
    }
}
?>
