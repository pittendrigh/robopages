<?php
@session_start();
include_once("conf/globals.php");
include_once("Link.php");
include_once("plugin.php");

class galleryMode extends plugin 
{
    protected $imageList;

    function thumbMemer($Img, $label)
    {
        $img = '<img src="'.$Img.'" alt="'.basename($Img).'"/>'; 
        $ret = '';
        $ret = ' <div class="thumbMeme">' . "\n";
        $ret .= ' <p class="thumbImg">' . $img . '</p>' . "\n";
        $ret .= ' <p class="thumblbl">' . StaticRoboUtils::mkLabel($label) . '</p>';
        $ret .= '</div>' . "\n";
        return $ret;
    }

    // This is a book. Unlike the general robopages case ALL pages are *.htm and all images are in roboresources/pics
    function mkLink($imgSrc, $htmFileName)
    {
        global $sys_thumb_links;
        $ret = '';
        $base = StaticRoboUtils::stripSuffix(basename($imgSrc));

        $imgLabel='';
        $ret = "\n\n" . '<div class="galleryNavigation">' . "\n";
        $ret .= '<a href="?robopage='.$_SESSION['currentDirUrl'] . $htmFileName.'">';

        $imgSrc = str_replace($_SESSION['currentDirPath'], $_SESSION['currentDirUrl'] , $imgSrc);

        $tpath = dirname(dirname($imgSrc)) . '/thumbs/tn-' . $base. '.jpg';
        if (@stat($_SERVER['DOCUMENT_ROOT'] . $tpath))
        {
               $thumb = '<img src="' . $tpath.  '" alt="' . $base . '"/>';
               $imgLabel = "\n" . $this->thumbMemer($tpath, $base) . "\n";
        }
        else
        {
               $thumb = '<img src="' . $imgSrc.  '" alt="' . $base . '"/>';
               $imgLabel = "\n" . $this->thumbMemer($imgSrc, $base) . "\n";

        }

        $ret .= $imgLabel . '</a>' . "\n";
        $ret .= '</div>';

        return $ret;
    }

    function getImageFilenames()
    {
        $ret = '';
if(stat($_SESSION['currentDirPath'] . 'roboresources/galleryMode/chapterImages'))
{
        $lines = file($_SESSION['currentDirPath'] . 'roboresources/galleryMode/chapterImages');
        $cnt = count($lines);
        //for ($i=0; $i<$cnt; $i++)
        foreach ($lines as $aline)
        {
           //$aline = $lines[$i];
           $lineChunks = explode("|",  $aline);
           $src = trim($lineChunks[0]);
           $src = str_replace("_ROBOPATH_", $_SESSION['currentClickDirUrl'], $src);
           $htmFile = trim($lineChunks[1]);
           $this->imageList[$src] = $htmFile;
        }
}else{
 echo "file ouch on ".$_SESSION['currentDirPath'] . "roboresources/galleryMode/chapterImages<br/>";
}
        return;
    }

    function getOutput($divid)
    {
        global $sys_show_suffixes, $sys_thumb_links;
        $this->getImageFilenames();

        $ret = '<h1> This Chapter&apos;s Images </h1>';

        if(isset($this->imageList))
        $cnt = count($this->imageList);
        if(isset($this->imageList))
        {
           foreach(array_keys($this->imageList) as $aKey)
           {
              $htmFileName = $this->imageList[$aKey];
              $ret .= "\n" . $this->mkLink($aKey,$htmFileName) . "\n";
           } 
        }
        return $ret;
    }
}
