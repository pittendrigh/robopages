<?php

@session_start();

include_once("plugin.interface.php");

class filesLister 
{

    public function getOutput($dir)
    {
        $cnt = 0;
        $filePaths = array();

    $displayPath=$dir;
    if($dir == null)
    {
          $dir = $_SESSION['currentDirPath'];
          $displayPath = $_SESSION['currentDirPath'];
          $displayPath = ltrim(rtrim($_SESSION['currentDirUrl'], '/'),'/');
    }
 

        if($displayPath == '' || $displayPath == '/')
            $displayPath = 'root directory';
        else
            $displayPath = str_replace($_SESSION['prgrmDocRoot'],'',$displayPath);

        $ret = '<br/><span class="small"><b> Files in ' . $displayPath . '</b><br/>';
        if ($dir_handle = opendir($dir))
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
