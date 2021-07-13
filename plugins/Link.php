<?php
/*
 * * Links are:
 * * 1) internal files ?robopage=Birds/robin.jpg ?robopage=Birds/ ?robopage=Birds/vulture.htm 
 * * ...with linkTargetType in (fragment,dir,image,url,label) or what ever else comes from roboMimeTyper()
 * * 3) label  Category.lbl ($file[0]) or stripSuffix(??)
 * * 4) url to somwhere else $file[0]
 * *
 * * label may is what is sent in as the label, which may or may not be basename($href)
 */

class Link
{
    protected $fileLine;
    public $href;
    public $label;
    public $linkTargetType;
    public $target;

    // linkTargetType is determined externally, usually with roboMimeTyper(basename($someFilePathOrHref))
    // If ever needed--from a $link object--we could basename($this->href)

    function __construct($fileLine)
    {
        $this->fileLine=$this->href=$this->label
          =$this->linkTargetType=$this->target='';

        $this->fileLine = trim($fileLine);
        $tokens = explode("::", $this->fileLine);
        $this->href = trim($tokens[0]);
        $this->label = trim($tokens[1]);
        if (isset($tokens[2]))
            $this->linkTargetType = trim($tokens[2]);
        else
            $this->linkTargetType = 'unknown';
        if(isset($tokens[2]) && $tokens[2] != null && $tokens[2] == 'url')
            $target = "_blank";

        //$this->dbg();
    }

    function dbg()
    {
        echo "fileLine: ", $this->fileLine, "<br />";
        echo "href: ", $this->href, "<br />";
        echo "label: ", $this->label, "<br />";
        echo "linkTargetType: ", $this->linkTargetType, "<br />";
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
