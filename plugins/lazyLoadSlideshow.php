<?php

@session_start();
include_once("roboMimeTyper.php");
include_once("conf/globals.php");

class lazyLoadSlideShow extends plugin {

    var $slideShowPath;
    var $slideShowUrl;
    var $interval;
    var $slidesNameString;

    function __construct() {
        
    }

    function mkButtons() {

        $intervalDisplay = $this->interval / 1000;
        $ret =
                "\n\n" . '
<div id="Cntrl">
   <input class="booton" type="button" value="Quit" id="quit" onclick="quit()"/>
   <input class="booton" type="button" value="pause" id="stoggle" onclick="toggle()"/>
   <input class="booton" type="button" value="next" id="ntoggle" onclick="nextImage()"/>
   <input class="booton" type="button" value="prev" id="ptoggle" onclick="prevImage()"/>
   &nbsp; &nbsp; &nbsp; '."\n".'<b class="booton"> Frame Rate </b><input type="button" value="-" onclick="decInterval()"/> 
   <p style="display: inline;" id="incrementDisplay">' . $intervalDisplay . '</p>
   <input type="button" value="+" onclick="incInterval()"/> 
</div>
               ';

        return $ret;
    }

    function mkJS() {

    $ret = '
    <script type="text/javascript">

       var defaultName = \''. $_SESSION['firstSlide'] . '\';
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
          var str = \' t: \' + thumbIDX + \' c: \' + cnt ;  
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
          dbg(\'<b> \' +baseName(mainPicObj.src).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
       }

       function thumbShuffle()
       {
            // cnt has just now been changed by changeImage() slideRight() or slideLeft()
            if(on == 1)
            {
               //clearInterval(intervalHandle);

               var mainPicObj = document.getElementById(\'currentSlideImg\');
               var thumbZeroObj  = document.getElementById(\'tn-0\');

               var foo = cnt;
               var imgPath = namesArray[cnt];
               mainPicObj.setAttribute("src", imgPath); 
               thumbZeroObj.setAttribute("value", imgPath); 
               thumbZeroObj.setAttribute("src", imgPath); 
               thumbZeroObj.setAttribute("title", foo % 6); 

               // i=1 because we already set tn-0
               var idx = cnt;
               for(i=1; i<6; i++)
               {
                  thumbName = \'tn-\' + i;
                  thumbObj = document.getElementById(thumbName);
                  idx = cnt + i;
                  if(idx >= max)
                  {
                    idx = 0;
                  }

                  var thumbSrc = idx2NameHash[idx];
                  //if(thumbSrc == null)
                  //  alert(\'null thumbSrc \' + obj.imgPath);
                  thumbObj.setAttribute("src", thumbSrc);
                  thumbObj.setAttribute("value", thumbSrc);
                  thumbObj.setAttribute("title", i);
               }
               //intervalHandle=setInterval("advanceImage()", interval);
             }
       }

       function slideRight()
       {
        if(on == 1)
        {
           clearInterval(intervalHandle);
           cnt = cnt + 6;     
           if(cnt >= max)
           {
              cnt = 0; 
           }
           thumbShuffle();
           intervalHandle=setInterval("advanceImage()", interval);
         } 
       }

       function slideLeft()
       {
        if(on == 1)
        {
           clearInterval(intervalHandle);
           cnt = cnt - 6; 
           if(cnt <= 0)
                cnt = max - 6;
           thumbShuffle();
           intervalHandle=setInterval("advanceImage()", interval);
        }
       }

       function changeImageFromThumb(obj)
       {
            clearInterval(intervalHandle);
            var mainDisplayImage = document.getElementById(\'currentSlideImg\');
            var imgPath = obj.getAttribute("value");
            cnt = name2IDXHash[imgPath]; 
            resetMainImage(obj); 

            intervalHandle=setInterval("advanceImage()", interval);
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

              var foo = cnt;
              thumbIDX = foo % 6; 
              var thumbName = \'tn-\' + thumbIDX;
 
              var thumbObj = document.getElementById(thumbName);
              var imgObj = document.getElementById(\'currentSlideImg\');
              if(imgPath)
              {
                 dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
                 //alert(imgPath);
                 imgObj.setAttribute("src", imgPath);
              }
               
              if(thumbObj != null)
              {
                thumbObj.setAttribute("src",imgPath);
                thumbObj.setAttribute("value", imgPath); 
                thumbObj.setAttribute("title", thumbIDX);
              }
          }
       }

       function nextImage()
       {
              var picLabel;
              cnt++;
              if(cnt >= max)
                    cnt = 0;
              var imgPath = idx2NameHash[cnt];

              var foo = cnt;
              thumbIDX = foo % 6; 
              var thumbName = \'tn-\' + thumbIDX;
 
              var thumbObj = document.getElementById(thumbName);
              var imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

               
              if(thumbObj != null)
              {
                thumbObj.setAttribute("src",imgPath);
                thumbObj.setAttribute("value", imgPath); 
                thumbObj.setAttribute("title", thumbIDX);
              }
              dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
       }

       function prevImage()
       {

              var picLabel;
              cnt--;
              if(cnt < 0)
                    cnt = max;
              var imgPath = idx2NameHash[cnt];

              var foo = cnt;
              thumbIDX = foo % 6; 
              var thumbName = \'tn-\' + thumbIDX;
 
              var thumbObj = document.getElementById(thumbName);
              var imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

               
              if(thumbObj != null)
              {
                thumbObj.setAttribute("src",imgPath);
                thumbObj.setAttribute("value", imgPath); 
                thumbObj.setAttribute("title", thumbIDX);
              }
              dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"").replace("_"," ").replace("-"," ") + \'</b>\');
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


       function stopSlideshow()
       {
         // not used....zap it or fix this....to not hard-coded 
         var uri = \'?page=' . $_SESSION['currentDirUrl'] . '\';
         window.location.replace(uri);
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
            rollNow();
         }
       }

      function quit()
      {
        location.replace("'.$_SERVER['PHP_SELF']."?page=".$_SESSION['currentDirUrl'].'");
      } 

    </script>';

        return $ret;
    }

    function is_image($file) {
        $ret = FALSE;
        if (stristr($file, '.jpg') || stristr($file, '.jpeg') || stristr($file, '.gif') || stristr($file, '.png')
        ) {
            $ret = TRUE;
        }

        return $ret;
    }

    function getImageFilenames($dir) {

        $ret='';

        //echo "getImageFilenames on $dir <br/>";
        $_SESSION['firstSlide'] = '';
        if ($dir_handle = opendir($dir)) {
            while (($file = readdir($dir_handle)) != false) {
                if ($file != '.' && $file != '..') {
                //echo $file, "<br/>"; 
                    if ($this->is_image($file)) {
                          $dbg = ','.  $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $file;
                          $_SESSION['firstSlide'] = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/' . $file;
                  //echo $_SESSION['firstSlide'], "<br/>";
                          $ret .= $dbg;
                    }
                }
            }
            closedir($dir_handle);
        } 
       $this->slidesNameString = $ret;
       return $ret;
    }

    

    function getOutput($divid = null) {
        global $sys_interval;
        $ret = '';
        $imgNames = array();

        if (isset($sys_interval))
            $this->interval = $sys_interval;
        else if(!isset($this->interval))
            $this->interval = 1000;

        $this->slideShowPath = $_SESSION['currentDirPath'] . 'roboresources/slideshow/';
        //echo $this->slideShowPath , "<br/>";
        $this->slideShowUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/';
        $this->getImageFilenames($this->slideShowPath);

        $ret .= $this->mkJS();
        $imgNames = explode (",", $this->slidesNameString);
        //echo count($imgNames), "<br/>";
        $stop = count($imgNames);

        $ret .= '<div id="slideShowContainer" > ';
        $ret .= $this->mkButtons();

/*
               for($i=0; $i<6; $i++)
               {
                 global $imgNames; 
                 $anImageName = $imgNames[$i];
                 $thumbID = 'tn-' . $i; 
                
       $ret .= "      <p class=\"thumbContainer\"> 
                            <img class=\"thumb\" id=\"".$thumbID."\" 
                             title=\"$i\" 
                             onclick=\"changeImageFromThumb(this)\" 
                             src=\"systemimages/fuzzball.png\" alt=\"\"/> 
                       </p>";
               }
*/
        $ret .= '<script type="text/javascript"> rollNow(); </script>';
        //echo $_SESSION['firstSlide'], "<br/>";
        $ret .=  '<p id="Dbg"><b>' . basename($_SESSION['firstSlide']) . '</b></p>
                 <img id="currentSlideImg" src="'.$_SESSION['firstSlide'].'" alt="'.basename($_SESSION['firstSlide']).'" /> ';
        $ret .=  '</div>' . "\n\n";


        return ($ret);
    }

}

?>
