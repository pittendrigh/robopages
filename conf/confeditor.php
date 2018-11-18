<?php

function BeginHTML($cssfile = null)
{
    $ret = '<!DOCTYPE HTML>';

    $ret .= '<html><head>
          <title>editconfig</title>';

    if ($cssfile != null)
        $ret .= '<link rel="stylesheet" type="text/css" href="' . $cssfile . '" >';
    $ret .= '</head>';

    return $ret;
}

function EndHtml()
{
    echo '</body></html>';
}

function showConfForm()
{
    $ret = BeginHTML('../css/robo.css');
    $ret .= '<div id="wrapper"><div id="main-disp"><fieldset><legend> Robopage Configuration Editor </legend>';
    $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '" method="post"> ';

    $firsttime = true;
    if (@stat("globals.php"))
    {
        $firsttime = false;
        $lines = file("globals.php");
    } else if (@stat("globals.dist.php"))
    {
        $lines = file("globals.dist.php");
    }
    $cnt = count($lines) - 1;
    for ($i = 1; $i < $cnt; $i++)
    {
        $aline = preg_replace("/\"|\\$|;/", "", $lines[$i]);
        $tokens = explode("=", $aline);
        $ret .= "<b>" . $tokens[0] . '</b> &nbsp; <input type="text" size="48" name="' . $tokens[0] . '" value="' . trim($tokens[1]) . '">';
        if ($firsttime)
            $ret .= " &nbsp; &nbsp; <b> Edit as needed.</b>";
        $ret .= "<br>";
    }

    $ret .= '<p><input type="submit" name="submit" value="Submit">,</p> ';
    $ret .= '</form>';
    $ret .= EndHtml();
    return $ret;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET')
    echo showConfForm();
else
{
    $fp = fopen("globals.php", "w");
    fwrite($fp, "<?php\n");
    while (list($k, $v) = each($_POST))
    {
        if (!stristr($k, "submit"))
        {
            if (strstr($k, "bool"))
                $line = sprintf("$%s=%s;\n", $k, $v);
            else
                $line = sprintf("$%s=\"%s\";\n", $k, $v);
            fwrite($fp, $line);
        }
    }
    fwrite($fp, "?>\n");
    unlink("globals.dist.php");
    header("location: " . dirname(dirname($_SERVER['PHP_SELF'])));
}
?>
