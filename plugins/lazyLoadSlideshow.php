<?php

 @session_start();
 include_once("roboMimeTyper.php");
 include_once("conf/globals.php");

 class lazyLoadSlideShow extends plugin
 {

   var $slideShowPath;
   var $slideShowUrl;
   var $interval;
   var $slidesNameString;

   function __construct()
   {
     
   }

   function mkButtons()
   {

     $intervalDisplay = $this->interval / 1000;
     $ret = "\n\n" . '
<div id="Cntrl">
   <input class="booton" type="button" value="Quit" id="quit" onclick="quit()"/>
   <input class="booton" type="button" value="pause" id="stoggle" onclick="toggle()"/>
   <input class="booton" type="button" value="next" id="ntoggle" onclick="nextImage()"/>
   <input class="booton" type="button" value="prev" id="ptoggle" onclick="prevImage()"/>
   &nbsp; &nbsp; &nbsp; ' . "\n" . '<b class="booton"> Frame Rate </b><input type="button" value="-" onclick="decInterval()"/> 
   <p style="display: inline;" id="incrementDisplay">' . $intervalDisplay . '</p>
   <input type="button" value="+" onclick="incInterval()"/> 
</div>
               ';

     return $ret;
   }

   function mkJS()
   {

     $ret = '
    <script type="text/javascript">

       var defaultName = \'' . $_SESSION['firstSlide'] . '\';
       var cnt=-1;
       var max=0;
     
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
          
          var dbg=document.getElementById(\'Dbg\');
          for (i=0; i<namesArray.length; i++)
          {
                 name = namesArray[i]; 
                 if(dbg != null)
                  dbg.innerHTML=name;
                 name2IDXHash[name] = i;
                 idx2NameHash[i] = name;
          }
          advanceImage();
          intervalHandle=setInterval("advanceImage()", interval);
       }

       function resetInterval() 
       {
          clearInterval(intervalHandle);
          timer = setInterval(function () {
          console.log("restarted interval");
          test1();
          }, 3000); 
       }

       function baseName(path)
       {
           if(path == null)
              alert(\'path null\');
           var ret = path.split("/").reverse()[0];
           return (ret);
       }

       function dbg(message)
       {
        if(on == 1)
        {
          var dbgObj = document.getElementById(\'Dbg\');
          var name = namesArray[cnt];
          if(message == null )
              dbgObj.innerHTML = str; 
          else
              dbgObj.innerHTML = message; 
        }
       }

       function resetMainImage(obj)
       {
          var mainPicObj = document.getElementById(\'currentSlideImg\');
          mainPicObj.setAttribute("src", obj.getAttribute("src"));
          //dbg(\'<b> \' +baseName(mainPicObj.src).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
       }


       function advanceImage()
       {
           if(on == 1)
           {
              var picLabel;
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              var imgPath = idx2NameHash[cnt];

              var imgObj = document.getElementById(\'currentSlideImg\');
           
              if(imgObj && imgPath)
              {
                 var dbgObj=document.getElementById(\'Dbg\');
                 var tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
                 tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
                 dbgObj.innerHTML = \'<b>\' + tmpStr + \'</b>\';

                 imgObj.setAttribute("src", imgPath);
              }
               
          }
       }

       function nextImage()
       {
              var picLabel;

         var btn = document.getElementById(\'stoggle\');
         if(on == 1)
         {
            on = 0;
            btn.value=\'resume\';
         }
              
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              var imgPath = idx2NameHash[cnt];

 
              var imgObj = document.getElementById(\'currentSlideImg\');

              if(imgObj != null && imgPath != null && imgPath.length != 0)
              {
                 imgObj.setAttribute("src", imgPath);
              }
               
              var dbg=document.getElementById(\'Dbg\');
              var tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
              tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
              dbg.innerHTML = \'<b>\' + tmpStr + \'</b>\';
       }

       function prevImage()
       {

              var picLabel;
              cnt--;
              if(cnt < 0)
                    cnt = max;
              var imgPath = idx2NameHash[cnt];
         var btn = document.getElementById(\'stoggle\');
         if(on == 1)
         {
            on = 0;
            btn.value=\'resume\';
         }

 
         var imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

              var dbg=document.getElementById(\'Dbg\');
              var tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
              tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
              dbg.innerHTML = \'<b>\' + tmpStr + \'</b>\';
       }

      function incInterval()
      {
         var iobj = document.getElementById("incrementDisplay");
         interval += 2000;
         if(interval > 5000)
         {
            interval = 5000;
         } 
         intervalDisplay = interval/1000;
         iobj.innerHTML = intervalDisplay.toString();
         clearInterval(intervalHandle);
         intervalHandle=setInterval("advanceImage()", interval);
         return false;
      }

      function decInterval()
      {
         var dobj = document.getElementById("incrementDisplay");
         interval -= 1000;
         if(interval < 1000)
         {
            interval = 1000;
         } 
         intervalDisplay = interval/1000;
         dobj.innerHTML = intervalDisplay.toString();
         clearInterval(intervalHandle);
         intervalHandle=setInterval("advanceImage()", interval);
         return false;
      }


       function toggle()
       {
         var btn = document.getElementById(\'stoggle\');

         if(on == 1)
         {
            on = 0;
            btn.value=\'resume\';
         }
         else
         {
            on = 1;
            btn.value=\'pause\';
         }
       }

      function quit()
      {
        location.replace("' . $_SERVER['PHP_SELF'] . "?page=" . $_SESSION['currentDirUrl'] . '");
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

     //echo "getImageFilenames on $dir <br/>";
     $_SESSION['firstSlide'] = '';
     if ($dir_handle = opendir($dir))
     {
       while (($file = readdir($dir_handle)) != false)
       {
         if ($file != '.' && $file != '..')
         {
           //echo $file, "<br/>"; 
           if ($this->is_image($file))
           {
             // use this if or not. If not then chop the first comma as a last step
             if (strlen($ret) > 0)
               $dbg = ',' . $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $file;
             else
               $dbg = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $file;
             $_SESSION['firstSlide'] = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $file;
             $ret .= $dbg;
           }
         }
       }
       closedir($dir_handle);
     }
     $this->slidesNameString = $ret;
     return $ret;
   }

   function getOutput($divid = null)
   {
     global $sys_interval;
     $ret = '';
     $imgNames = array();

     if (isset($sys_interval))
       $this->interval = $sys_interval;
     else if (!isset($this->interval))
       $this->interval = 1000;

     $this->slideShowPath = $_SESSION['currentDirPath'] . 'roboresources/slideshow/';
     //echo $this->slideShowPath , "<br/>";
     $this->slideShowUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/';
     $this->getImageFilenames($this->slideShowPath);

     $ret .= $this->mkJS();
     $imgNames = explode(",", $this->slidesNameString);
     //echo count($imgNames), "<br/>";
     $stop = count($imgNames);

     $ret .= $this->mkButtons();

     $ret .= '<script type="text/javascript"> rollNow(); </script>';
     $ret .= '<p id="Dbg" style="margin: 0.5em 0 0 0; padding: 0;"><b>'
         . staticRoboUtils::mkLabel(basename(staticRoboUtils::stripSuffix($_SESSION['firstSlide'])))
         . '</b></p> <img id="currentSlideImg" src="' . $_SESSION['firstSlide'] . '" alt="' . basename($_SESSION['firstSlide'])
         . '" /> ';


     return ($ret);
   }

 }

?>
