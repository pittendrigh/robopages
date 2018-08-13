<?php

 @session_start();
 include_once("plugin.php");
 include_once("conf/globals.php");

 class arcade extends plugin
 {

   var $slideShowPath;
   var $slideShowUrl;
   var $interval;
   var $slidesNameString;

   function __construct()
   {
     
   }

   function mkJS()
   {

     $ret = '
    <!-- lazy load slideShow -- one image at a time until they\'re all in -->
    <script type="text/javascript">

       var cnt=-1;
       var max=0;
       var thumbIDX = 0;
     
       var on = 1;
       var interval = ' . $this->interval . ';
       var intervalDisplay = interval/1000;
       var intervalHandle=null;
       var namesArray;
       var name2IDXHash  = new Object();
       var idx2NameHash  = new Object();

       function  rollNow()
       {
          var str = "' . $this->slidesNameString . '";
          namesArray = str.split(\',\');
          max = namesArray.length;
          var name;
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
           var ret = path.split("/").reverse()[0];
           return (ret);
       }

       function dbg(message)
       {
        if(on == 1)
        {
          var dbgObj = document.getElementById(\'Dbg\');
          var name = namesArray[cnt];
          var str = \' t: \' + thumbIDX + \' c: \' + cnt ;  
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
              var imgPath = idx2NameHash[cnt];

              var foo = cnt;
              thumbIDX = foo % 6; 
              var thumbName = \'tn-\' + thumbIDX;
 
              var thumbObj = document.getElementById(thumbName);
              var imgObj = document.getElementById(\'currentSlideImg\');
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
