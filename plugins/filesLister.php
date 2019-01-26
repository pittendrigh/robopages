<?php

@session_start();

include_once("plugin.interface.php");

class filesLister 
{

    public function getOutput($divid)
    {
        $cnt = 0;
        $filePaths = array();

        $path = ltrim(rtrim($_SESSION['currentDirUrl'], '/'),'/');
        if($path == '' || $path == '/')
            $path = 'root directory';
        $ret = '<br/><span class="small"><b>Downloadable Files in ' . $path . ':</b><br/>';
        if ($dir_handle = opendir($_SESSION['currentDirPath']))
        {
            while (($file = readdir($dir_handle)) != false)
            {
                if ($file != '.' && $file != '..')
                {
                    if (!is_dir($_SESSION['currentDirPath'].$file))
                    {
                        $filePaths[$cnt] = $file;
                        $cnt++;
                    }
                }
            }
        }

        sort($filePaths, SORT_NATURAL | SORT_FLAG_CASE);
       
        $format=0; 
        for ($i = 0; $i < $cnt; $i++)
        {
                      $format++;
                      if($format % 4 == 0) $ret .= ' <br/> ';
                      $afile = $filePaths[$i];
                      //echo $afile, "...<br/>";
                      $robopage = str_replace($_SESSION['prgrmDocRoot'],'',$afile);
                      $downloadLabel = basename($afile);
                      $alink = <<<ENDO
                      &nbsp; &nbsp;
                      <a href="?robopage=$robopage" download="$downloadLabel">$downloadLabel</a>
ENDO;
                       $ret .= $alink;

        }
      return $ret . '</span>';
    }


}

?>
