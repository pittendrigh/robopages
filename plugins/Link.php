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
    public $fileLine;
    public $href;
    public $label;
    public $linkTargetType;

    // linkTargetType is determined externally, usually with roboMimeTyper(basename($someFilePathOrHref))
    // If ever needed--from a $link object--we could basename($this->href)

    function __construct($fileLine)
    {
        $this->fileLine = trim($fileLine);
        $tokens = explode("::", $this->fileLine);
        $this->href = trim($tokens[0]);
        $this->label = trim($tokens[1]);
        if (isset($tokens[2]))
            $this->linkTargetType = trim($tokens[2]);
        else
            $this->linkTargetType = 'unknown';
        //$this->dbg();
    }

    function defaultLink()
    {
        return '<a href="?robopage='.$this->href.'">'.$this->label."</a>\n";
    }

    function dbg()
    {
        echo "fileLine: ", $this->fileLine, "<br />";
        echo "href: ", $this->href, "<br />";
        echo "label: ", $this->label, "<br />";
        echo "linkTargetType: ", $this->linkTargetType, "<br />";
        echo "<br />";
    }
}
?>
