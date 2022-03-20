<?php
@session_start();
include_once("plugin.php");

class mkBreadcrumbs extends plugin
{

    function __construct()
    {
        
    }

    function mkBreadcrumbs($who = null)
    {
        global $sys_home_link_label;
        $home = 'home';

        if(isset($sys_home_link_label) && $sys_home_link_label != '')
           $home = $sys_home_link_label;

        if (!isset($who))
            $who = $_SERVER['PHP_SELF'];

        $explodee = isset($_GET['robopage']) ? $_GET['robopage'] : '';
        $dirs = explode('/', $explodee);

        $cnt = count($dirs);
        $base = '';

        $breadcrumbs = "\n" . '<a class="breadcrumbs" href="' . $who . '"><b>('.$home.')</b>/</a>';

        for ($i = 0; $i < $cnt; $i++)
        {
            $adir = trim($dirs[$i]);
            //echo "adir $i: ", $adir, "<br/>";
            $stripper = ":" . $_SESSION['prgrmUrlRoot'] . ":";
            $base .= preg_replace($stripper, "", $adir);
            if (is_dir($_SESSION['prgrmDocRoot'] . $base))
                $base .= '/';
            if ($dirs[$i] != null)
            {
                $breadcrumbs .= '<a class="breadcrumbs" href="';

                //$linkLabel = preg_replace("/-|_/", " ", $dirs[$i]);
                $linkLabel = preg_replace("/^.*_/", "", $dirs[$i]);
                if ($i < $cnt - 1)
                    $breadcrumbs .= $_SERVER['PHP_SELF'] . '?robopage=' . trim($base) . '">' . $linkLabel . '/</a>';
                else
                    $breadcrumbs .= $_SERVER['PHP_SELF'] . '?robopage=' . trim($base) . '">' . $linkLabel . '</a>';
            }
        }

        $ret = '<a class="breadcrumbs" href="' . $who . '"></a><b class="nav">' . $breadcrumbs . '</b>' . "\n";
        return $ret;
    }

    function getOutput($divid)
    {
        return $this->mkBreadcrumbs();
    }

}
?>
