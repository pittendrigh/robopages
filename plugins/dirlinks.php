<?php
include_once("dynamicNavigation.php");
include_once("mkThumbs.php");
include_once("conf/globals.php");
include_once("adminPlugin.interface.php");
include_once("adminPlugin.php");

class dirlinks extends adminPlugin implements adminPluginInterface
{
    var $mimer;
    protected $dirlinksFilePath;
    protected $lcnt;
    var $dynamicNavigation;

    function __construct()
    {
        $this->mimer = new roboMimeTyper();
        $this->dirlinksFilePath = $_SESSION['currentDirPath'] . 'dirlinks';
        $this->init();
        $this->dynamicNavigation = new dynamicNavigation();
        $this->dynamicNavigation->gatherLinks();
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
        //$fp = fopen($_SESSION['currentDirPath'] . 'dirlinks', "w");

        $pvals = explode(",", $_POST['new_order']);
        $vcnt = count($pvals);
        for ($i = 0; $i < $vcnt; $i++)
        {
           if(!isset($pvals[$i]))
             break;
            $pline = $pvals[$i];
            $ret .= $pline . "<br/>";
            fwrite($fp, $pline . "\n");
        }
        fclose($fp);
       return $ret;
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
        $this->dynamicNavigation->fileKeys = null;
        $this->dynamicNavigation->linkshash = null;
        $this->dynamicNavigation->gatherLinks();

        unlink($this->dirlinksFilePath);
        $fp = fopen($this->dirlinksFilePath, "w");

        foreach($this->linkshash as $akey)
        {
            $link = $this->dynamicNavigation->linkshash[$akey];

            if ($link != null && $link->href != '')
            {
                $line = $this->mkFileLine($link);
                fwrite($fp, $line);
            }
        }
        fclose($fp);
    }

    function getSecureOutput($divid)
    {
        $ret = ''; 
        if($_SERVER['REQUEST_METHOD'] == 'POST' && $_POST['mode'] == 'saveDirlinks')
        {
            $ret = "<h3>dirlinks:</h3>";
            $ret .= $this->writeNewlyOrderedLinks();
            return $ret . '<br/><a href="?robopage='. $_GET['robopage'] . '&amp;layout=nerd"> <button>Saved now back to the Admin Screen</button></a>' ;
        }
 
        $this->lcnt = count($this->dynamicNavigation->linkshash);
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

        $ret .= <<<zENDO
   <form action="?robopage=$currentDirUrl&amp;layout=dirlinks" method="post">
    <input type="hidden" name="mode" value="saveDirlinks"/>
    <p><input type="hidden" id="new_order" name="new_order" /> </p>
    <button type="submit" id="sumbit"> Save this ordering </button>
</form>
    <br/><a href="?robopage=$currentDirUrl&amp;layout=nerd"><button> Cancel </button></a><br/>

<h4 style="text-align: center;"> 
Drag and drop the links below--up and down--using a left mouse click 
<br/>
 Then click Save 
</h4>
<ul id="dalist" class="sortable">
zENDO;

 $i=0;
        foreach(array_keys($this->dynamicNavigation->linkshash) as $akey)
        {
            $link = $this->dynamicNavigation->linkshash[$akey];
            if ($link != null && $link->href != '')
            {
                $dbg = $this->mkLIline($link, $i);
                $ret .= $dbg;
            }
           $i++;
        }

/*
** perhaps should put this js in a dirlinks.xml so the js is linked in the head
** However, this way dirlinks can be instantiated from a case statement in RobopageAdmin.php
** without needing a layouts/dirlinks.xml (which is there anyway, in case....)
*/
        $ret .= <<<mENDO
       </ul>
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script> $(function() { $('.sortable').sortable(); });</script>
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
mENDO;

        return $ret;
    }

}
?>
