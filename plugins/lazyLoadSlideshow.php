<?php
@session_start();
include_once("roboMimeTyper.php");
include_once("conf/globals.php");

class lazyLoadSlideShow extends plugin
{
    protected
            $firstSlide;
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

       protected cnt=-1;
       protected max=0;
     
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
          namesArray.sort();
          max = namesArray.length;
          protected name;
          
          protected dbg=document.getElementById(\'Dbg\');
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
           protected ret = path.split("/").reverse()[0];
           return (ret);
       }

       function dbg(message)
       {
        if(on == 1)
        {
          protected dbgObj = document.getElementById(\'Dbg\');
          protected name = namesArray[cnt];
          if(message == null )
              dbgObj.innerHTML = str; 
          else
              dbgObj.innerHTML = message; 
        }
       }

       function resetMainImage(obj)
       {
          protected mainPicObj = document.getElementById(\'currentSlideImg\');
          mainPicObj.setAttribute("src", obj.getAttribute("src"));
          //dbg(\'<b> \' +baseName(mainPicObj.src).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
       }


       function advanceImage()
       {
           if(on == 1)
           {
              protected picLabel;
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              protected imgPath = idx2NameHash[cnt];

              protected imgObj = document.getElementById(\'currentSlideImg\');
           
              if(imgObj && imgPath)
              {
                 protected dbgObj=document.getElementById(\'Dbg\');
                 protected tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
                 tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
                 dbgObj.innerHTML = \'<b>\' + tmpStr + \'</b>\';

                 imgObj.setAttribute("src", imgPath);
              }
               
          }
       }

       function nextImage()
       {
              protected picLabel;

         protected btn = document.getElementById(\'stoggle\');
         if(on == 1)
         {
            on = 0;
            btn.value=\'resume\';
         }
              
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              protected imgPath = idx2NameHash[cnt];

 
              protected imgObj = document.getElementById(\'currentSlideImg\');

              if(imgObj != null && imgPath != null && imgPath.length != 0)
              {
                 imgObj.setAttribute("src", imgPath);
              }
               
              protected dbg=document.getElementById(\'Dbg\');
              protected tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
              tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
              dbg.innerHTML = \'<b>\' + tmpStr + \'</b>\';
       }

       function prevImage()
       {

              protected picLabel;
              cnt--;
              if(cnt < 0)
                    cnt = max;
              protected imgPath = idx2NameHash[cnt];
         protected btn = document.getElementById(\'stoggle\');
         if(on == 1)
         {
            on = 0;
            btn.value=\'resume\';
         }

 
         protected imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

              protected dbg=document.getElementById(\'Dbg\');
              protected tmpStr = baseName(imgPath.replace(\'.jpg\',\'\'));
              tmpStr = tmpStr.substring(tmpStr.lastIndexOf(\'_\')+1);
              dbg.innerHTML = \'<b>\' + tmpStr + \'</b>\';
       }

      function incInterval()
      {
         protected iobj = document.getElementById("incrementDisplay");
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
         protected dobj = document.getElementById("incrementDisplay");
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
         protected btn = document.getElementById(\'stoggle\');

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
        location.replace("' . $_SERVER['PHP_SELF'] . "?robopage=" . $_SESSION['currentDirUrl'] . '");
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

        $cnt = 0;
        $ret = '';
        $slidePaths = array();

        if ($dir_handle = opendir($dir))
        {
            while (($file = readdir($dir_handle)) != false)
            {
                if ($file != '.' && $file != '..')
                {
                    if ($this->is_image($file))
                    {
                        $slidePaths[$cnt] = $file;
                        $cnt++;
                    }
                }
            }
        }
        closedir($dir_handle);

        sort($slidePaths, SORT_NATURAL | SORT_FLAG_CASE);
        for ($i = 0; $i < $cnt; $i++)
        {
            $val = $slidePaths[$i];
            $slidePaths[$i] = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $val;
        }

        $this->firstSlide = $slidePaths[0];
        $this->slidesNameString = implode(",", $slidePaths);
        return $this->slidesNameString;
    }

    function getOutput($divid = null)
    {
        global $sys_interval;
        $ret = '';

        if (isset($sys_interval))
            $this->interval = $sys_interval;
        else if (!isset($this->interval))
            $this->interval = 1000;

        $this->slideShowPath = $_SESSION['currentDirPath'] . 'roboresources/slideshow/';

        $this->slideShowUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/';
        $this->getImageFilenames($this->slideShowPath);

        $ret .= $this->mkJS();

        $ret .= $this->mkButtons();

        $ret .= '<script type="text/javascript"> rollNow(); </script>';

        $ret .= '<p id="Dbg" style="margin: 0.5em 0 0 0; padding: 0;"><b>'
                . StaticRoboUtils::mkLabel(basename(StaticRoboUtils::stripSuffix($this->firstSlide)))
                . '</b></p> <img id="currentSlideImg" src="' . $this->firstSlide . '" alt="' . basename($this->firstSlide)
                . '" /> ';



        return ($ret);
    }

}
?>
