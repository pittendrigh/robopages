<?php
include_once("dynamicNavigation.php");
include_once("mkThumbs.php");
include_once("conf/globals.php");

// extends or has a dynamivNavigation?
// implements an init to guarantee isLoggedIn and nimda? 
// and a reliable selfUrl?
//
class dirlinks extends dynamicNavigation
{
    var $mimer;
    var $dirlinksFilePath;
    var $lcnt;

    function __construct()
    {
        $this->mimer = new MimetypeHandler();
        $this->dirlinksFilePath = $_SESSION['currentDirPath'] . 'dirlinks';
        $this->checkAuthorityCredentials();
        $this->init();
        $this->gatherLinks();
    }

    function checkAuthorityCredentials()
    {

        if (!isset($_SESSION['privilege']) || ($_SESSION['privilege'] != 'nimda'))
            header("location: ?layout=auth");
    }

    function mkLIline($link, $i)
    {
        global $sys_show_suffixes;


        $lbl = '';
        if ($sys_show_suffixes && strstr($link->href, "robopage="))
        {
            $chunks = explode("=", $link->href);
            $lbl = basename($chunks[1]);
        }
        else
        {
            $lbl = $link->label;
        }
        $ret = '<li style="width: 90%; border: 1px solid green;" src="' . $i . '">';
        $ret .= $link->href . '::' . $lbl . '</li>' . "\n";
        return($ret);
    }

    function writeNewlyOrderedLinks()
    {
        $fp = fopen($this->dirlinksFilePath, "w");
        //?robopage=rambunctious.jpg::Rambunctious.jpg

        $pvals = explode(",", $_POST['new_order']);
        $vcnt = count($pvals);
        for ($i = 0; $i < $vcnt; $i++)
        {
            $pline = $pvals[$i];
            //echo "pline: ", $pline, "<br/>";
            $ret .= $pline . "<br/>";
            fwrite($fp, $pline . "\n");
        }
        fclose($fp);
        chmod($this->dirlinksFilePath, 0777);
        //echo '<script type="text/javascript"> window.location.reload();</script>';
        header("Location: ?robopage=" . $_SESSION['currentDirUrl'] . $_SESSION['currentDisplay']);
    }

    function mkFileLine($link)
    {
        global $sys_show_suffixes;
        $ret = '';

        $lbl = '';
        if ($sys_show_suffixes && strstr($link->href, "robopage="))
        {
            $chunks = explode("=", $link->href);
            $lbl = basename($chunks[1]);
        }
        else
            $lbl = $link->label;

        $ret .= preg_replace(":\/\/[\/]*:", '/', trim($link->href)) . '::' . $lbl . "\n";
        return($ret);
    }

    function mkDirlinksFile()
    {
        $this->orderedkeys = null;
        $this->linkshash = null;
        $this->gatherLinks();

        if (count($this->dirs) > 0)
            $this->orderedkeys = array_merge($this->dirs, $this->orderedkeys);
        unlink($this->dirlinksFilePath);
        $fp = fopen($this->dirlinksFilePath, "w");

        $lcnt = count($this->linkshash);
        for ($i = 0; $i < $lcnt; $i++)
        {
            $akey = $this->orderedkeys[$i];
            $link = $this->linkshash[$akey];

            if ($link != null && $link->href != '')
            {
                $line = $this->mkFileLine($link);
                fwrite($fp, $line);
            }
        }
        fclose($fp);
        chmod($this->dirlinksFilePath, 0777);
    }

    function getOutput($divid)
    {
        $this->lcnt = count($this->linkshash);
        $self = $this->selfUrl;
        $ret = '';

        $ret .= <<<ENDO
    <style>
        section {
            display: block;
        }
        .sortable {
            margin: auto;
            padding: 0;
            width: 310px;
            -webkit-touch-callout: none;
            -webkit-user-select: none;
            -khtml-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .sortable li {
            list-style: none;
            border: 1px solid #CCC;
            background: #F6F6F6;
            font-family: "Tahoma";
            color: #1C94C4;
            margin: 5px;
            padding: 5px;
            height: 22px;
        }
   </style>
ENDO;

        $ret .= <<<zENDO
<section>
<form name="form" id="form" action="$self" method="post">
    <p>$self</p>
    <p><input type="archive" id="new_order" name="new_order" /> </p>
    <p><input type="archive" name="mode" value="saveDirlinks"/> </p>
    <p><button type="button" id="sumbit"> Save this ordering </button></p>
    <p><button type="button" id="ccancel"> Cancel </button></p>
</form>
<h4 style="text-align: center;"> Drag and drop the links below--up and down--using a left mouse click 
<br/>
 Then click Save </h4>
<ul id="dalist" class="sortable">
zENDO;

        if (count($this->dirs) > 0)
            $this->orderedkeys = array_merge($this->dirs, $this->orderedkeys);
        for ($i = 0; $i < $this->lcnt; $i++)
        {
            $akey = $this->orderedkeys[$i];
            $link = $this->linkshash[$akey];

            if ($link != null && $link->href != '')
            {
                $dbg = $this->mkLIline($link, $i);
                //echo htmlentities($dbg) . "<br/>";
                $ret .= $dbg;
            }
        }


        $ret .= <<<mENDO
       </ul>
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script src="jquery.sortable.js"></script>
    <script> $(function() { $('.sortable').sortable(); });</script>
    <script>
        $("#ccancel").click(function() {
            //window.history.back();
              window.location.replace("?jqMode=Edit");
        });
    </script>
    <script>
        $("#sumbit").click(function() {
            var data = '';

            $("#dalist li").each(
                    function(i, el)
                    {
                        //data += $(el).attr("src") + ":" + $(el).text() + ",";
                        data += $(el).text() + ",";
                    }
            );

            data = data.substring(0, data.length - 1);
            $('#new_order').val(data);
            $("#form").submit();
            
        }
        );
    </script>
    </section>
mENDO;

        return $ret;
    }

}
?>
