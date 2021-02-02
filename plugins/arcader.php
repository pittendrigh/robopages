<?php

include_once("targetedArcade.php");

class arcader 
{

 var $targetedArcade;

 function __construct($path)
 {
   $this->arcader = new targetedArcade($path);

   echo "\n". $this->arcader->getOutput('') . "\n";
 } 
  

}

?>
