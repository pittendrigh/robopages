<?php
@session_start();
include_once("plugin.php");
include_once("conf/globals.php");

class arcade extends plugin
{
    protected
            $slideShowPath;
    protected
            $slideShowUrl;
    protected
            $interval;
    protected
            $slidesNameString;

    function __construct()
    {
        
    }

    function mkJS()
    {

        $ret = '
    <!-- lazy load slideShow -- one image at a time until they\'re all in -->
    <script type="text/javascript">

       protected cnt=-1;
       protected max=0;
       protected thumbIDX = 0;
     
       protected on = 1;
       protected interval = ' . $this->interval . ';
       protected intervalDisplay = interval/1000;
       protected intervalHandle=null;
       protected namesArray;
       protected name2IDXHash  = new Object();
       protected idx2NameHash  = new Object();

       function  rollNow()
       {
          protected str = "' . $this->slidesNameString . '";
          namesArray = str.split(\',\');
          max = namesArray.length;
          protected name;
          for (i=0; i<namesArray.length; i++)
          {
                 name = namesArray[i]; 
                 name2IDXHash[name] = i;
                 idx2NameHash[i] = name;
          }
          nextImage();
          intervalHandle=setInterval("nextImage()", interval);
       }


       function baseName(path)
       {
           //if(path == null)
           //   alert(\'path null\');
           protected ret = path.split("/").reverse()[0];
           return (ret);
       }

       function dbg(message)
       {
        if(on == 1)
        {
          protected dbgObj = document.getElementById(\'Dbg\');
          protected name = namesArray[cnt];
          protected str = \' t: \' + thumbIDX + \' c: \' + cnt ;  
          if(message == null )
              dbgObj.innerHTML = str; 
          else
              dbgObj.innerHTML = message; 
        }
       }

       function nextImage()
       {
           if(on == 1)
           {
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              protected imgPath = idx2NameHash[cnt];

              protected foo = cnt;
              thumbIDX = foo % 6; 
              protected thumbName = \'tn-\' + thumbIDX;
 
              protected thumbObj = document.getElementById(thumbName);
              protected imgObj = document.getElementById(\'currentSlideImg\');
             if(imgPath) 
             {
              dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
              imgObj.setAttribute("src", imgPath);
             }

               
              if(thumbObj != null)
              {
                thumbObj.setAttribute("src",imgPath);
                thumbObj.setAttribute("value", imgPath); 
                thumbObj.setAttribute("title", thumbIDX);
              }
              dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
          }
       }


    </script>';

        return $ret;
    }

    function is_image($file)
    {
        $ret = FALSE;
        if (stristr($file, '.jpg') || stristr($file, '.jpeg') || stristr($file, '.gif') || stristr($file, '.png')
        )
        {
            $ret = TRUE;
        }

        return $ret;
    }

    function getImageFilenames($dir)
    {

        $ret = '';

        //echo $dir, "<br/>";
        $_SESSION['firstSlide'] = '';
        if ($dir_handle = @opendir($dir))
        {
            while (($file = readdir($dir_handle)) != false)
            {
                if ($file != '.' && $file != '..')
                {
                    //echo $file, "<br/>"; 
                    if ($this->is_image($file))
                    {
                        $dbg = ',' . $_SESSION['currentClickDirUrl'] . 'roboresources/arcade/' . $file;
                        $_SESSION['firstSlide'] = $file;
                        $ret .= $dbg;
                    }
                }
            }
            closedir($dir_handle);
        }
        $this->slidesNameString = $ret;
        return $ret;
    }

    function getOutput($divid)
    {
        global $sys_interval;
        $ret = '';

        if (isset($sys_interval))
            $this->interval = $sys_interval;
        else if (!isset($this->interval))
            $this->interval = 1000;

        $this->slideShowPath = $_SESSION['currentDirPath'] . 'roboresources/arcade/';
        $this->slideShowUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/arcade/';
        $this->getImageFilenames($this->slideShowPath);

        $ret .= $this->mkJS();
        $imgNames = explode(",", $this->slidesNameString);
        $stop = count($imgNames);

        $ret .= '<p id="Dbg"> &nbsp; </p>';

        $ret .= '<img id="currentSlideImg" src="' . $_SESSION['currentClickDirUrl'] . 'roboresources/arcade/' . $_SESSION['firstSlide'] . '" alt="arcade photo" />';


        $ret .= '<script type="text/javascript"> rollNow(); </script>';


        return ($ret);
    }

}
?>
