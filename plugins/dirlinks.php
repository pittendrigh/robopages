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
    protected $dirlinksFilePath;
    protected $lcnt;

    function __construct()
    {
        $this->mimer = new roboMimeTyper();
        $this->dirlinksFilePath = $_SESSION['currentDirPath'] . 'dirlinks';
        $this->checkAuthorityCredentials();
        $this->init();
        $this->gatherLinks();
    }

    function checkAuthorityCredentials()
    {

        if (!isset($_SESSION['privilege']) || ($_SESSION['privilege'] != 'nimda'))
            header("location: ?layout=authUtils");
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
        $ret = '<li style="width: auto; border: 1px solid green;" src="' . $i . '">';
        $ret .= $link->href . '::' . $lbl . '</li>' . "\n";
        //echo htmlentities($ret);
        return($ret);
    }

    function writeNewlyOrderedLinks()
    {
        $ret = '';
        $fp = fopen($this->dirlinksFilePath, "w");

        $pvals = explode(",", $_POST['new_order']);
        $vcnt = count($pvals);
        for ($i = 0; $i < $vcnt; $i++)
        {
            $pline = $pvals[$i];
            //echo "pline: ", $pline, "<br/>";
            $ret .= $pline . "<br/>";
            fwrite($fp, $pline . "\n");
        }
        chmod($this->dirlinksFilePath, 0777);
        fclose($fp);
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
        $this->fileKeys = null;
        $this->linkshash = null;
        $this->gatherLinks();

        //if (count($this->dirKeys) > 0)
         //   $this->fileKeys = array_merge($this->imageKeys,$this->dirKeys, $this->fileKeys);
        unlink($this->dirlinksFilePath);
        $fp = fopen($this->dirlinksFilePath, "w");

        //$lcnt = count($this->fileKeys);
        //for ($i = 0; $i < $lcnt; $i++)
        foreach($this->linkshash as $akey)
        {
            //$akey = $this->fileKeys[$i];
            $link = $this->linkshash[$akey];

            if ($link != null && $link->href != '')
            {
                $line = $this->mkFileLine($link);
                echo $line,"<br/>";
                fwrite($fp, $line);
            }
        }
        fclose($fp);
        chmod($this->dirlinksFilePath, 0777);
    }

    function getOutput($divid)
    {
 
        if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['mode'] == 'saveDirlinks')
        {
            $this->writeNewlyOrderedLinks();
            return '<a href="?robopage='. $_GET['robopage'] . '&amp;layout=nerd"> <button>Saved now back to the Admin Screen</button></a>' ;
        }
 
        $this->lcnt = count($this->linkshash);
        $ret = '';

        $ret .= <<<ENDO
    <style>
        .sortable {
            margin: auto;
            padding: 0;
            width: auto;
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

$currentDirUrl = $_SESSION['currentDirUrl'];
    //<p><button type="button" id="cancel"> Cancel </button></p>

        $ret .= <<<zENDO
   <form action="?robopage=' . $currentDirUrl . '&amp;layout=dirlinks" method="post">
    <input type="hidden" name="mode" value="saveDirlinks"/>
    <p><input type="hidden" id="new_order" name="new_order" /> </p>
    <button type="submit" id="sumbit"> Save this ordering </button>
</form>
<h4 style="text-align: center;"> 
Drag and drop the links below--up and down--using a left mouse click 
<br/>
 Then click Save 
</h4>
<ul id="dalist" class="sortable">
zENDO;

        //if (isset($this->dirKeys) && count($this->dirKeys) > 0)
         //   $this->fileKeys = array_merge($this->dirKeys, $this->fileKeys);
        //for ($i = 0; $i < $this->lcnt; $i++)
 $i=0;
        foreach(array_keys($this->linkshash) as $akey)
        {
           // $akey = $link = null;
           //if(isset($this->fileKeys[$i]))
            //  $akey = $this->fileKeys[$i];
            //else 
             // break;
/*
            if(isset($this->linkshash[$akey]))
                $link = $this->linkshash[$akey];
            else 
              break;
*/
             //echo "<br/>damn akey: ", $akey, "<br/>";
             $link = $this->linkshash[$akey];
            if ($link != null && $link->href != '')
            {
                $dbg = $this->mkLIline($link, $i);
                $ret .= $dbg;
            }
           $i++;
        }

        $ret .= <<<mENDO
       </ul>
    <script src="js/jquery.min.js"></script>
    <script src="js/jquery.sortable.js"></script>
    <script> $(function() { $('.sortable').sortable(); });</script>
    <script>
        $("#ccancel").click(function() {
            //window.history.back();
              window.location.replace("?jqMode=Edit");
        });
    </script>
    <script>
        $("#sumbit").click(function() {
            protected data = '';

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
mENDO;

        return $ret;
    }

}
?>
