<?php

@session_start();
include_once("plugins/roboMimeTyper.php");
include_once("conf/globals.php");

class lazyLoadSlideShow extends plugin {

    protected $slideShowPath;
    protected $slideShowUrl;
    protected $interval;
    protected $firstSlide;
    protected $slidesNameString;

    function __construct($path=null) { }

    function mkButtons() {

        $intervalDisplay = $this->interval / 1000;
        $ret =
                "\n\n" . '
<div id="Cntrl">
   <input type="button" value="Quit" id="quit" onclick="quit()"/>
   <input type="button" value="pause" id="stoggle" onclick="toggle()"/>
   <input type="button" value="next" id="ntoggle" onclick="nextImage()"/>
   <input type="button" value="prev" id="ptoggle" onclick="prevImage()"/>
   &nbsp; &nbsp; &nbsp; '."\n".'<b> Frame Rate </b><input type="button" value="-" onclick="decInterval()"/> 
   <p style="display: inline;" id="incrementDisplay">' . $intervalDisplay . '</p>
   <input type="button" value="+" onclick="incInterval()"/> 
</div>
               ';

        return $ret;
    }

    function mkJS() {

    $ret = '
    <script>

       var defaultName = \''. $this->firstSlide . '\';
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
          //alert(str);
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
        message = message.replace(/^.*_/g," ");
        
          var dbgObj = document.getElementById(\'Dbg\');
          var name = namesArray[cnt];
          dbgObj.innerHTML = message; 
       }

       function resetMainImage(obj)
       {
          var mainPicObj = document.getElementById(\'currentSlideImg\');
          mainPicObj.setAttribute("src", obj.getAttribute("src"));
          dbg(\'<b> \' +baseName(mainPicObj.src).replace(/.jpg|.png|.gif/i,"") + \'</b>\');
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
              if(imgPath)
              {
                 dbg(\'<b> \' +baseName(imgPath).replace(/.jpg|.png|.gif/i,"") + \'</b>\');
                 imgObj.setAttribute("src", imgPath);
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
              var cap = baseName(imgPath).replace(/.jpg|.png|.gif/i," ");
              dbg(cap);

              var imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

       }

       function prevImage()
       {

              var picLabel;
              cnt--;
              if(cnt < 0)
                    cnt = max;
              var imgPath = idx2NameHash[cnt];

 
              var imgObj = document.getElementById(\'currentSlideImg\');
              imgObj.setAttribute("src", imgPath);

              var cap = baseName(imgPath).replace(/.jpg|.png|.gif/i," ");
              dbg(cap);
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
         var uri = \'?robopage=' . $this->slideShowUrl . '\';
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
        location.replace("'.$_SERVER['PHP_SELF']."?robopage=".$_SESSION['currentDirUrl'].'");
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

        $this->firstSlide = '';
        if ($dir_handle = opendir($dir)) {
            while (($file = readdir($dir_handle)) != false) {
                if ($file != '.' && $file != '..') {
                    if ($this->is_image($file)) {
                          $dbg = ','.  $this->slideShowUrl . $file;
                          $this->firstSlide = $this->slideShowUrl . $file;
                          $ret .= $dbg;
                    }
                }
            }
            closedir($dir_handle);
        } 
       $this->slidesNameString = $ret;
       return $ret;
    }

   
    function setup()
    {
        global $sys_interval;
        if (isset($sys_interval))
            $this->interval = $sys_interval;
        else if(!isset($this->interval))
            $this->interval = 1000;
       $this->slideShowPath = $_SESSION['currentDirPath'] . 'roboresources/slideshow/';
       $this->slideShowUrl = $_SESSION['currentClickDirUrl'] . 'roboresources/slideshow/';
       $this->getImageFilenames($this->slideShowPath);
       
    } 

    function getOutput($divid = null) {
        $ret = '';
        $imgNames = array();

           
        $this->setup(); 

        $ret .= $this->mkJS();
        $imgNames = explode (",", $this->slidesNameString);
        $stop = count($imgNames);

        $ret .= '<div id="slideShowContainer" > ';
        $ret .= $this->mkButtons();

        $ret .= '<script> rollNow(); </script>';
        $ret .=  '<p id="Dbg"><b>' . preg_replace("/^.*_/"," ", basename($this->firstSlide)) . '</b></p>
                 <img id="currentSlideImg" src="'.$this->firstSlide.'" alt="'.basename($this->firstSlide).'" /> ';
        $ret .=  '</div>' . "\n\n";


        return ($ret);
    }

}

?>
