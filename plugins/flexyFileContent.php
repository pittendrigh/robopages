<?php
include_once("roboPather.class.php");
include_once("Link.php");
include_once("processBackTics.class.php");
//include_once("upload.php");
include_once("roboMimeTyper.php");

@session_start();

include_once("conf/globals.php");
include_once("plugin.php");

class flexyFileContent extends plugin
{
    protected
            $mimer;

    function __construct()
    {
        $this->mimer = new roboMimeTyper();
    }

    function mkNextPrevButtons()
    {
        $ret = '';
        if (@stat($_SESSION['currentDirPath'] . '/slideshow'))
        {
            $self = $_SERVER['PHP_SELF'];
            if (isset($_GET['robopage']))
                $self = $_SERVER['PHP_SELF'] . "?robopage=" . $_GET['robopage'];

            $ret .= <<<ENDO
<div class="nextprev">
<form action="$self" method="post">
<input type="submit" name="next"          value="next" /> 
<input type="submit" name="prev"          value="prev" /> 
</form>
</div>

ENDO;
        }
        return ($ret);
    }

    function handleForm()
    {
        // grep -iHn actionItem fix this with an is_image function later
        // assume a slideshow dir has symlinks to images only, for now
        // assuming good input now!

        $slides = array();
        $dir = $_SESSION['currentDirPath'] . '/slideshow';
        $handle = @opendir($dir);
        $currentIdx = $i = $idx = 0;
        $headerlink = '';

        while ($handle && ($file = @readdir($handle)) !== FALSE)
        {
            if ($file[0] == '.')
                continue;

            $slides[$i] = "?robopage=" . $_SESSION['currentDirUrl'] . $file;
            if (strstr($_SERVER['REQUEST_URI'], $file))
            {
                $currentIdx = $i;

                if (isset($_POST['next']))
                {
                    $idx = $currentIdx + 1;
                }
                else
                {
                    $idx = $currentIdx - 1;
                }
            }
            $i++;
        }

        $totalPix = $i;

        if ($idx >= $totalPix)
            $idx = 0;
        else if ($idx < 0)
            $idx = $totalPix - 1;

        $headerlink = $slides[$idx];
        $headerlink .= "&idx=$idx";

        if (isset($headerlink) && $headerlink != '')
            @header("location: $headerlink");
    }

    function getOutput($divid)
    {
        if ($this->mimer == null)
            $this->mimer = new roboMimeTyper();
        $ret = $linkTargetType = '';


        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $ret .= $this->handleForm();
        }
        $tentativeDisplayFile = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];
        $linkTargetType = "unknown";
        if (@stat($tentativeDisplayFile))
            $linkTargetType = $this->mimer->getRoboMimeType($tentativeDisplayFile);
        //else echo "bad stat: ", $tentativeDisplayFile. "<br/>"; 

        if ($linkTargetType == 'unknown')
        {
            // return $_SESSION['currentDisplay'] . ' Not Found';
            return $_SESSION['currentDisplay'];
        }

        switch ($linkTargetType)
        {
            case "dir":
                $ret .= $this->mkHTMLFragmentArea();
                break;
            case "image":
                $ret .= $this->mkImageArea();
                break;
            case "pdf":
                $ret .= $this->mkPDFArea();
                break;
            case "iframe":
                $ret .= $this->mkIframeArea();
                break;
            case "text":
                $ret .= $this->mkTextArea();
                break;
            case "fragment":
                $ret .= $this->mkHTMLFragmentArea();
                break;
            case "highlight":
                $ret .= $this->mkHighlightArea();
                break;
            case "video":
                $ret .= $this->mkVideoArea();
                break;
            case "audio":
                $ret .= $this->mkAudioArea();
                break;
            case "url":
                header("location: " . $url);
                break;
            default:
                $ret .= $this->mkHTMLFragmentArea();
        }


        if ($linkTargetType == 'fragment' && strstr($ret, '`'))
        {
            $backTicker = new processBackTics();
            $ret = $backTicker->evalBackTics($ret);
        }
        if (strstr($ret, '_ROBOPATH_'))
        {
            $roboPather = new roboPather();
            $ret = $roboPather->fixROBOPATHs($ret);
        }

        //$ret .= '<div style="clear:both;"> &nbsp; </div>';
        return $ret;
    }

    function getCaption()
    {
        $ret = $caption = '';
        $base = StaticRoboUtils::stripSuffix($_SESSION['currentDisplay']);
        $capfile = $_SESSION['currentDirPath'] . '/' . $base . ".cap";
        $caption = @file_get_contents($capfile);
        if ($caption != null)
        {
            $ret .= '<p class="image_caption">';
            $ret .= $caption . '</p>';
        }
        return $ret;
    }

    function mkAudioArea()
    {
        $ret = '';
        $soundfile = $_SESSION['currentClickDirUrl'] . $_SESSION['currentDisplay'];
        $ret .= <<<ENDO
<audio controls autoplay>
  <source src="$soundfile" type="audio/mp3">
Your browser does not support the audio element.
</audio>
ENDO;
        return $ret;
    }

    function mkPDFArea()
    {
        $ret = $src = $filePath = '';
        $filePath = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];
        $ret .= '<a style="text-decoration: underline;" href="' . $filePath . '" download> (Download ' . $_SESSION['currentDisplay'] . ')</a>';

        $src = $_SESSION['currentClickDirUrl'] . $_SESSION['currentDisplay'];
        $ret .= <<<ENDO
<object data="$src" type="application/pdf" style="margin:0; padding:0; width: 100%; min-height: 30em;">
        alt : <a href="$src">$src</a>
    </object>
ENDO;

        return $ret;
    }

    function mkIframeArea()
    {
        $ret = $src = '';
        $srcFile = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];
        $src = file_get_contents($srcFile);
        $ret = '<iframe src="' . $src . '" style="margin:0; padding:0; width: 100%; min-height: 30em;"></iframe>';
        return $ret;
    }

    function mkImageArea()
    {
        global $sys_maxImgWidth, $sys_maxImgHeight;
        $ret = '';
        $caption = $this->getCaption();

        $src = preg_replace('://[\/]*:', '/', $_SESSION['currentClickDirUrl'] . $_SESSION['currentDisplay']);
        $ret .= '<p><b>' . ucfirst(StaticRoboUtils::mkLabel($_SESSION['currentDisplay'])) . '</b></p>';
        $imgtag = '<img class="main-image" src="' . $src . '" alt="' . $_SESSION['currentDisplay'] . '" />';
        //$ret .= htmlentities($imgtag);

        $ret .= $imgtag;
        if ($caption != null)
            $ret .= $caption;
        return $ret;
    }

    function mkTextArea()
    {

        $file = $_SESSION['currentDirPath'] . $_SESSION['currentDisplay'];
        // next line is probably stupid....put this in the text file if you want it
        //$ret = '<h4>' . $_SESSION['currentDisplay'] . '</h4>';

        $raw = file_get_contents($file);
        $tmp .= preg_replace('/(\n)/', "<br>", $raw);
        $ret .= preg_replace('/(\s)/', " ", $tmp);

        return $ret;
    }

    function mkHTMLFragmentArea()
    {
        $ret = '';
        $ret .= @file_get_contents($_SESSION['currentDirPath'] . $_SESSION['currentDisplay']);
        // grep -iHn actionItem *php the following a bit ugly. Perhaps better to map book.xml to an inherited class
        if (isset($_SESSION['layout']) && strstr($_SESSION['layout'], 'book'))
        {
            $ret = preg_replace('/src=\"/', "src=\"/fragments/" . $_SESSION['opfDirUrl'] . '/', $ret);
        }
        return $ret;
    }

    function mkVideoArea()
    {
	$ret = '';
        $vid = $_SESSION['currentClickDirUrl'] . $_SESSION['currentDisplay'];
	echo "vid: ", $vid, "<br/>";
         
        $ret .= ' 
<video width="500" height="375" controls>
  <source src="'.$vid.'" type="video/mp4">
Your browser does not support the video tag.
</video>';
        return $ret;
    }

    function mkHighlightArea()
    {
        $ret = '<h4>' . $_SESSION['currentDisplay'] . '</h4>';
        $ret .= highlight_file($_SESSION['currentDirPath'] . $_SESSION['currentDisplay'], TRUE);
        return $ret;
    }

}
?>
