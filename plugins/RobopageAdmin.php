<?php
@session_start();

include_once ("AuthUtils.php");
include_once ("editor.php");
include_once ("uploader.php");
include_once ("filesLister.php");
include_once ("mkSlides.php");

class RobopageAdmin extends plugin
{
    //var $editor;
    var $currentUrl;
    var $nimdaistrableDivID;

    //function __construct() { }

    function init()
    {
        $this->chmod_r($_SESSION['prgrmDocRoot'], 0755);
        parent::init();
    }

    //function _destruct() { echo "yo, destructor <br/>"; exit; }

    function destroy_dir($dir)
    {
        if (!is_dir($dir) || is_link($dir))
            return unlink($dir);
        foreach (scandir($dir) as $file)
        {
            if ($file == '.' || $file == '..')
                continue;
            if (!$this->destroy_dir($dir . DIRECTORY_SEPARATOR . $file))
            {
                chmod($dir . DIRECTORY_SEPARATOR . $file, 0755);
                if (!$this->destroy_dir($dir . DIRECTORY_SEPARATOR . $file))
                    return false;
            };
        }
        return rmdir($dir);
    }

    function chmod_r($path, $octal)
    {
        $dir = new DirectoryIterator($path);
        foreach ($dir as $item)
        {
            $dbg = $item->getPathname();
            if ($dbg[0] == '.')
                continue;
            try
            {
                @chmod($item->getPathname(), $octal);
            }
            catch (Exceptian $a)
            {
                
            }
            if ($item->isDir() && !$item->isDot())
            {
                try
                {
                    $this->chmod_r($item->getPathname(), $octal);
                }
                catch (Excepton $e)
                {
                    echo "Error $e->getMessage() <br/>";
                }
            }
        }
    }

    function checkAuthorityCredentials()
    {
        $ret = TRUE;
        if (!isset($_SESSION['privilege']) || ($_SESSION['privilege'] != 'nimda'))
        {
            $ret = FALSE;
        }
        return $ret;
    }

    function zipData($source, $destination)
    {
        $zip = null;
        if (extension_loaded('zip'))
        {
            if (file_exists($source))
            {
                $zip = new ZipArchive();
                if ($zip->open($destination, ZIPARCHIVE::CREATE))
                {
                    $source = realpath($source);
                    if (is_dir($source))
                    {
                        $iterator = new RecursiveDirectoryIterator($source);
                        // skip dot files while iterating 
                        $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
                        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
                        foreach ($files as $file)
                        {
                            $file = realpath($file);
                            if (is_dir($file))
                            {
                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                            }
                            else if (is_file($file))
                            {
                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                            }
                        }
                    }
                    else if (is_file($source))
                    {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }
                return $zip->close();
            }
        }
        return false;
    }

    function zipToBackUp($source, $destination)
    {
        $zip = null;
        if (extension_loaded('zip') === true)
        {
            if (file_exists($source) === true)
            {
                $zip = new ZipArchive();

                if ($zip->open($destination, ZIPARCHIVE::CREATE) === true)
                {
                    $source = realpath($source);

                    if (is_dir($source) === true)
                    {

                        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);

                        foreach ($files as $file)
                        {
                            $file = realpath($file);

                            if (is_dir($file) === true)
                            {
                                $zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
                            }
                            else if (is_file($file) === true)
                            {
                                $zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
                            }
                        }
                    }
                    else if (is_file($source) === true)
                    {
                        $zip->addFromString(basename($source), file_get_contents($source));
                    }
                }

                return $zip->close();
            }
        }

        return false;
    }

    function getOutput($divid)
    {
        $ret = $mode = '';
        //StaticRoboUtils::dbg();
        if (!$this->checkAuthorityCredentials())
        {
            $ret = <<<ENDO
<button><a href="?layout=auth">Login First </button>
ENDO;
            return $ret;
        }


        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            if (isset($_POST['mode']))
            {
                $mode = $_POST['mode'];
            }
        }

        if ($mode != null)
        {
            switch ($mode)
            {

                case "logout":
                    $this->chmod_r($_SESSION['prgrmDocRoot'], 0555);
                    @session_start();
                    session_unset();
                    session_destroy();

                    echo '<script>window.location.href="/"</script>';
                    break;
                case "download":
                    $ret = '';
                    $farr = $this->getFilesList($_SESSION['currentDirPath']);

                    $cnt = 0;
                    foreach ($farr as $afile)
                    {
                        $robopage = str_replace($_SESSION['prgrmDocRoot'], '', $afile);
                        $downloadLabel = basename($afile);
                        $cnt++;
                        if ($cnt % 4 == 0)
                            $ret .= '<br/>';
                        $alink = <<<ENDO
                      &nbsp; &nbsp; <a href="?robopage=$robopage" download="$downloadLabel">$downloadLabel</a>
ENDO;
                        $ret .= $alink;
                    }

                    break;
                case "mkSlideshow":
                    $mkSlides = new mkSlides();
                    $mkSlides->getOutput('');
                    $robopage = $_GET['robopage'];
                    $ret = <<<ENDO
<button><a href="?robopage=$robopage&amp;layout=slideshow">Test that Slideshow </button><br/><br/>
<button><a href="?robopage=$robopage&amp;layout=nerd">Back to the Admin Scren</button><br/>
ENDO;
                    break;

                case "mkThumbs":
                    $thumbs = new mkThumbs();
                    $robopage = $_GET['robopage'];
                    $ret .= <<<ENDO
<button><a href="?robopage=$robopage&amp;layout=nerd">Back to the Admin Scren</button><br/>
ENDO;
                    $thumbLister = new filesLister();
                    $ret .= $thumbLister->getOutput($_SESSION['currentDirPath'] . 'roboresources/thumbs/');
                    $ret .= $thumbs->getOutput('');
                    break;

                case "saveDirlinks":
                case "dirlinks":
                    include_once("dirlinks.php");
                    $dirlinks = new dirlinks();
                    //if ($mode == 'dirlinks')
                    $ret .= $dirlinks->getOutput($this->nimdaistrableDivID);
                    break;
                case "uploadForm":
                case "uploadHandlePost":
                    $uploader = new uploader();
                    $ret .= $uploader->getOutput('');
                    break;
                case "renameDir":
                case "renameFile":
                    if (isset($_POST['newfilename']) && $_POST['newfilename'] != null)
                    {
                        if (!rename($_SESSION['currentDirPath'] . $_POST['filename'], $_SESSION['currentDirPath'] . $_POST['newfilename']))
                        {
                            $ret .= "renaming " . $_POST['filename'] . " to " . $_POST['newfilename'] . " failed! <br/>";
                        }
                        else
                        {
                            $ret = $this->showForm();
                        }
                    }
                    $ret = $this->showForm();
                    break;
                case "copyFile":
                    if (isset($_POST['copytofilename']) && $_POST['copytofilename'] != null)
                    {
                        if (!copy($_SESSION['currentDirPath'] . $_POST['filename'], $_SESSION['currentDirPath'] . $this->cleanName($_POST['copytofilename'])))
                        {
                            $ret .= "copy  " . $_POST['filename'] . " to " . $_POST['newfilename'] . " failed! <br/>";
                        }
                    }
                    $ret = $this->showForm();
                    break;

                case "deleteFile":
                    if (!unlink($_SESSION['currentDirPath'] . $_POST['filename']))
                    {
                        $ret .= "delete  " . $_POST['filename'] . " failed! <br/>";
                    }
                    else
                    {
                        $ret = $this->showForm();
                    }
                    break;

                case "deleteDir":
                    $this->zipData($_SESSION['currentDirPath'] . $_POST['deletedir']
                            , $_SESSION['prgrmDocRoot'] . 'roboresources/BAKS/' . basename($_POST['deletedir']) . '.zip');
                    //$this->destroy_dir($_SESSION['currentDirPath'] . $_POST['deletedir']);
                    $ret = $this->showForm();
                    break;
                case "createDir":
                    if (isset($_POST['newdirname']) && $_POST['newdirname'] != null)
                    {
                        if (!@mkdir($_SESSION['currentDirPath'] . $_POST['newdirname'], 0755))
                        {
                            $ret .= "mkdir  " . $_POST['newdirname'] . " failed! <br/>";
                        }
                        @chmod($_SESSION['currentDirPath'] . $_POST['newdirname'], 0755);
                    }
                    $ret .= $this->showForm();
                    break;
                case "editFile":
                case "createFile":
                    if (isset($_POST['filename']) && $_POST['filename'] != null)
                    {
                        echo "should start editor<br/>";

                        $editor = new editor('file');
                        $ret = $editor->getOutput('');
                    }
                    else
                    {
                        $ret .= $this->showForm();
                    }
                    break;
                case "newBlogEntry":
                    $newBlogEditor = new editor('blog');
                    $ret = $newBlogEditor->getOutput('');
                    break;
            }
        }
        else
        {
            $ret .= $this->showForm();
        }

        return $ret;
    }

    function dirChanger()
    {
        $ret = '';
        $dc = new dirChanger('');
        $ret .= '<div style="float: right; width: auto;"><h3> Directory Changer </h3>';
        $ret .= $dc->getOutput('');
        return $ret . '</div>';
    }

    function showForm()
    {
        $ret = '';
        $filesLister = new filesLister($ret);
        $ret .= '<h4>Current File Path: ' . $_SESSION['currentDirPath'] . '<h4>';
        $ret .= '<fieldset><legend>File Handling</legend>';
        $ret .= $this->logoutButton();
        $ret .= $this->dirChanger();
        $ret .= $this->createFileForm();
        $ret .= $this->editFileForm();
        //$ret .= $this->copyFileForm();
        $ret .= $this->renameFileForm();
        $ret .= $this->deleteFileForm();
        $ret .= $this->createDirForm();
        $ret .= $this->deleteDirForm();
        $ret .= $this->gotoUploadButton();
        $ret .= $this->mkSlideShowButton();
        $ret .= $this->mkThumbsButton();
        $ret .= $this->newBlogEntryButton();
        $ret .= $this->mkLinkOrdererButton();
        $ret .= $filesLister->getOutput('');
        $ret .= '</fieldset>';


        return($ret);
    }

    function getDirsList()
    {
        $str = "";
        $fd = opendir($_SESSION['currentDirPath']);
        while ($thingy = readdir($fd))
        {
            if ($thingy[0] == "." || $thingy == "LOGS" || $thingy == "roboresources" || $thingy == "nimda")
                continue;
            $candidate = $_SESSION['currentDirPath'] . $thingy;
            if (is_dir($candidate))
                $str .= "<option>" . $thingy . "</option>";
        }
        closedir($fd);
        return $str;
    }

    function logoutButton()
    {
        $ret = '<form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
           <input style="color: red; font-weight: bold;" type="submit" value="Logout -- important!" />
           <input type="hidden" name="mode" value="logout"/>
           </form>';

        return($ret);
    }

    function deleteDirForm()
    {
        $str = '<p class="widget"><form action="?robopage=' . $_SESSION['currentDirUrl']
                . '&amp;layout=nerd" method="post"> 
           <input type="submit" value="Delete Directory"/>
           <select name="deletedir"> ' . $this->getDirsList() . '</select>  <span class="small"> 
           <input type="hidden" name="mode" value="deleteDir"/>
           <span class="small"><b>(backup zip in ' . $_SESSION['currentDirUrl'] . 'roboresources/BAKS/)</span>
           </form></p>';

        return $str;
    }

    function downloadForm()
    {
        $ret = '';
        $self = '?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd&amp;mode=download';
        $ret = <<<ENDO
     <form action="$self" method="post">
      <input type="hidden" name="mode" value="download"/>
      <input type="submit" value="Download Files"/>
      </form>
ENDO;
        return $ret;
    }

    function renameFileForm()
    {
        $str = '<form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post">
            <input type="submit" value="Rename File"/>
           Rename <select name="filename"> ' . StaticRoboUtils::getFilesOptions($_SESSION['currentDirPath']) . '</select> 
            To:  <input type="text" name="newfilename"/>
           <input type="hidden" name="mode" value="renameFile"/>
           </form>';
        return $str;
    }

    function renameDirForm()
    {
        $str = '<form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;laout=nerd" method="post">
            <input type="submit" value="Rename Dir"/>
            Rename <select name="filename"> ' . $this->getDirsList() . '</select> 
            To  <input type="text" name="newfilename"/>
           <input type="hidden" name="mode" value="renameFile"/>
           </form>';
        return $str;
    }

    function copyFileForm()
    {
        $str = '<form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post">
            <input type="submit" value="Copy File"/>
            copy <select name="filename"> ' . $this->getFilesOptions() . '</select> 
            To  <input type="text" name="copytofilename"/>
            <input type="hidden" name="mode" value="copyFile"/>
           
           </form>';
        return $str;
    }

    function deleteFileForm()
    {
        $str = '
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
         <input type="submit" value="Delete File"/>
   
         <select name="filename"> ' . StaticRoboUtils::getFilesOptions($_SESSION['currentDirPath']) . '</select>  
        <input type="hidden" name="mode" value="deleteFile"/>
        </form>';
        return $str;
    }

    function mkLinkOrdererButton()
    {
        $str = '';
        $str = '
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
        <input type="submit" value="Reorder Links"/> (for page sets using a dynamic TOC)
        <input type="hidden" name="mode" value="dirlinks"/>
        </form>';
        return $str;
    }

    function mkThumbsButton()
    {
        $str = '<p>
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
        <input type="submit" value="Make Thumbnails"/>
        <input type="hidden" name="mode" value="mkThumbs"/>
        </form></p>';
        return $str;
    }

    function mkSlideShowButton()
    {
        $forDir = " for " . $_SESSION['currentDirUrl'];
        if (!isset($_SESSION['currentDirUrl']) || $_SESSION['currentDirUrl'] == '')
            ;
        {
            $forDir = '';
        }
        $str = '<p class="widget">
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
        <input type="submit" value="Make Slideshow ' . $forDir . '"/>
        <input type="hidden" name="mode" value="mkSlideshow"/>
        </form></p>';
        return $str;
    }

    function newBlogEntryButton()
    {
        $currentDirUrl = $_SESSION['currentDirUrl'];
        $ret = <<<ENDO
         <p class="widget">
         <form action="?robopage=$currentDirUrl&amp;layout=nerd" method="post">
          <input type="hidden" name="mode" value="newBlogEntry"/>
          <input type="submit" value="New Blog Entry"/>
         </form>
         </p>
ENDO;
        return $ret;
    }

    function gotoUploadButton()
    {
        $str = '<p class="widget">
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
        <input type="hidden" name="mode" value="uploadForm"/>
         <input type="submit" value="Upload Files"/>
        </form></p>';
        return $str;
    }

    function createDirForm()
    {
        $self = $_SESSION['currentDirUrl'];
        $str = <<<ENDO
  <form action="?robopage=$self&amp;layout=nerd" method="post"> 
         <input type="submit" value="Create Directory"/>
         <input type="text" name="newdirname"/>
        <input type="hidden" name="mode" value="Create Directory"/>
  </form>
ENDO;
        return $str;
    }

    function createFileForm()
    {
        $str = '
        <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
         <input type="submit" value="Create New File"/>
         <input type="text" size="16" name="filename"/>
        <input type="hidden" name="mode" value="createFile"/>
        </form>';
        return $str;
    }

    function getFilesList($dir, $filter = null)
    {
        $files = array();
        $temp = array();

        $fd = opendir($dir);
        if (isset($fd))
        {
            while ($thingy = readdir($fd))
            {
                if ($thingy[0] == "." || $thingy == "LOGS" || $thingy == "roboresources")
                    continue;

                $candidate = $dir . $thingy;

                if (is_dir($candidate) || strstr($thingy, "obopage"))
                    continue;
                $temp[$thingy] = $candidate;
            }

            foreach (array_keys($temp) as $afile)
            {
                $apath = $temp[$afile];
                if ($filter == null || $filter != null && preg_match("/" . $filter . "/", $apath))
                {
                    $files[$afile] = $apath;
                }
            }
        }
        closedir($fd);
        return $files;
    }

    function getFilesOptions($dir, $filter = null)
    {
        $farry = $this->getFilesList($dir, $filter);
        $opstr = '';
        foreach (array_keys($farry) as $akey)
        {
            $opstr .= '<option  value="' . $akey . '">' . $akey . '</option>';
        }
        return $opstr;
    }

    function editFileForm()
    {
        $str = '
         <form action="?robopage=' . $_SESSION['currentDirUrl'] . '&amp;layout=nerd" method="post"> 
          <input type="submit" value="Edit Existing File"/>
          Edit:  <select name="filename"> '
                . $this->getFilesOptions($_SESSION['currentDirPath'], "\.blog|\.lcm|\.htm|\.txt|\.blurb|\.frag|dirlinks|layout") . '</select> 
         <input type="hidden" name="mode" value="editFile"/>
         </form>';
        return $str;
    }

}
?>
