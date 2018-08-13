<?php

 @session_start();

// ...
 class processBackTics
 {

   function __construct()
   {
     
   }

   function evalBackTics($str)
   {
     $ret = '';

     $pos1 = strpos($str, '`');
     $ret .= substr($str, 0, $pos1);

     $rest = substr($str, $pos1 + 1);
     $pos2 = strpos($rest, '`');

     $cmd = substr($str, $pos1 + 1, $pos2);
     if ($cmd != null)
     {
       ob_start();
       eval($cmd);
       $ret .= ob_get_contents();
       @ob_end_clean();
     }

     $rest = substr($rest, $pos2 + 1);
     if (strstr($rest, '`'))
       $ret .= $this->evalBackTics($rest);
     else
       $ret .= $rest;

     return($ret);
   }

 }

?>
