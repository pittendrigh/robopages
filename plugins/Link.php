<?php

include_once("roboMimeTyper.php");

class Link
{

    protected $fileLine;
    public $href;
    public $label;
    public $desc;

    //public $key;

    function __construct($fileLine)
    {
        $this->roboMimer = new roboMimeTyper();
        $this->fileLine = trim($fileLine);
        $tokens = explode("::", $this->fileLine);
        $this->href = $tokens[0];
        $this->label = $tokens[1];
        if (isset($tokens[2]))
            $this->desc = $tokens[2];
        else
            $this->desc = '';
        /*
          if(isset($tokens[3]))
          $this->key = $tokens[3];
          else
          $this->key = '';
         */
    }

    /*
      function setKey($key)
      {
      $this->key = $key;
      }
     */

    function dbg()
    {
        echo "fileLine: ", $this->fileLine, "<br />";
        echo "href: ", $this->href, "<br />";
        echo "label: ", $this->label, "<br />";
        echo "desc: ", $this->desc, "<br />";
        //echo "key: [", $this->key, "]<br />";
        echo "<br />";
    }

    /*
      function getFileline() {
      return $this->fileLine;
      }

      function getHref() {
      return $this->href;
      }

      function getLabel() {
      return $this->label;
      }

      function getType() {
      return $this->roboMimeType;
      }
     */
}

?>
