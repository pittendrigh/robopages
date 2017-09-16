<?php

include_once("roboMimeTyper.php");

class Link {

    protected $fileLine;
    public $href;
    public $label;
    public $desc;

    //public $key;

    function __construct($fileLine) {
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

    function defaultLink($class = null) {

        if (stristr($this->label, "slideshow")) {
            $this->class = "slideshow";
        } else if ($class != null) {
            $this->class = $class;
        } else {
            $this->class = $this->roboMimeType;
        }

        $lbl = $this->label;

        if (strstr($this->href, "page")) {
            $hackComparitor = preg_replace(":^.*page=:", "", $this->href);
            if (is_dir($_SESSION['prgrmDocRoot'] . $hackComparitor)) {
                $lbl = '<img class="folder" src="systemimages/folder.png" alt="folder" />' . $lbl;
            }
        }

        $ret = '';
        $ret .= sprintf("<a class=\"%s\" href=\"%s\">%s</a>", $this->class, $this->href, $lbl);

        return $ret;
    }

    function dbg() {
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
