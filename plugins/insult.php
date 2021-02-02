<?php
@session_start();

include_once("plugin.php");

class insult extends plugin 
{
    protected $selfUrl;

    public function __construct()
    {
        $this->init();
    }

    public function init()
    {
        $this->selfUrl = $this->determineSelfUrl();
    }

    public function determineSelfUrl()
    {
        $ret = preg_replace('://[\/]*:', '/', '?robopage=' . $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']);
        $this->selfUrl = $ret;
        return($ret);
    }

    public function getSelfUrl()
    {
        return $this->selfUrl;
    }

    public function getOutput($divid)
    {
        $ret = '';

         $file = file('plugins/insults');
         
         $c0 = array();
         $c1 = array();
         $c2 = array();
         
         foreach ($file as $aline)
         {
          trim($aline);
          $tokens = explode(" ", $aline);
          $c0[]=$tokens[0];
          $c1[]=$tokens[1];
          $c2[]=$tokens[2];
         }
         
         
         $str0 = $c0[array_rand($c0)];
         $str1 = $c1[array_rand($c1)];
         $str2 = $c2[array_rand($c2)];
         $ret .= '<div style="width: 50%; margin: 0 auto;"><b>You ' .  ucfirst($str0) . " " . $str1. " " . $str2 . "</b></div>";

         $ret .= '<script> setInterval("window.location.reload()", 4000);</script>';

        return($ret);
    }
}
