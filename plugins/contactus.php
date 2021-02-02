<?php
@session_start();

include_once("plugin.php");

//ini_set('error_reporting',0);

class contactus extends plugin
{
    protected
            $keys = array();
    protected
            $names = array();
    protected
            $upload_folder = '/tmp/';

    function __construct()
    {
        $this->getNames();
    }

    function getOutput($divid)
    {

        if (count($_GET) > 1)
            exit;

        $address = $ret = "";

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $ret .= $this->handleGet();
        }
        else if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $ret .= $this->handlePost();
        }
        return ($ret);
    }

    // "names" is a file that contains a list of valid mailTo names
    // this file usually contains only one name but it can be more than one
    //
    function getNames()
    {
        $file = getcwd() . "/plugins/names";
        $lines = file($file);

        $cnt = count($lines);
        for ($i = 0; $i < $cnt; $i++)
        {
            $tokens = explode(":", trim($lines[$i]));
            $key = trim($tokens[0]);
            $address = trim($tokens[1]);
            $this->names[$key] = $address;
            $this->keys[$i] = $key;
        }
    }

    // mailTo comes from this list.  What ever is chosen, the mail will be sent to one and only one address 
    function mkToSelect()
    {

        $cnt = count($this->names);
        $this->keys = array_keys($this->names);
        $ret = '';
        $ret .= '<select name="toName">';
        for ($i = 0; $i < $cnt; $i++)
        {
            $ret .= '<option>' . $this->keys[$i] . '</option>';
        }
        $ret .= '</select>';
        return ($ret);
    }

    function handleGet()
    {
        $ret = '';
        $_SESSION['spam'] = 'didUseForm';
        //$ret .= '<div id="awrapper"><fieldset <legend class="alegend">Email </legend>';
        $ret .= '<fieldset>';
        $ret .= '<form action="' . $_SERVER['PHP_SELF'] . '?layout=contactus"  method="post" enctype="multipart/form-data">';

        $ret .= '
         <p style="margin: 0.5em;">  Your Name <input type="text" size="32" name="fromname"/></p>
         <p style="margin: 0.5em;"> <span style="color: red; text-shadow: #ffffff 1px 1px 2px; font-weight: bold"> 
                Return email address <input type="text"  size="32" name="fromaddress"/> required! </span> </p>
         <p style="margin: 0 0.5em;"> Subject <input type="text"  size="32"  name="subject"/></p>';

        $ret .= '<p style="margin: 0.5em;">   Send the following message to ' . $this->mkToSelect() . '</p>';

        $ret .= '
       <div><textarea style="width: 80%; min-height: 10em;" name="message" cols="50" rows="10"></textarea></div>
       <p> <input type="hidden" name="layout" value="contactus"/>   
       <p style="display: none;"> Tags <input type="text" name="keywords" value=""/>  </p>
       <input type="submit" name="submit" value="Send"/>  
       <input type="button" name="cancel" value="Cancel" onclick="window.history.back()"/>
       </p>
       </form></fieldset>';

        return $ret;
    }

    // upload stuff below is available but not currently set up or used in handleGet
    //
    function copyUploadedFile($uploadedFileName, $uploadedTmpName, $uploadedFileType, $uploadedFileSize, $uploadedErrorMsg)
    {
        //echo "uploadedFileName: ", $uploadedFileName, "<br/>";
        $errors = '';

        $type_of_uploaded_file = substr($uploadedFileName, strrpos($uploadedFileName, '.') + 1);

        $max_allowed_file_size = 100; // size in KB
        $allowed_extensions = array("jpg", "jpeg", "gif", "bmp");

        //Validations
        if ($uploadedFileSize > $max_allowed_file_size)
        {
            $errors .= "\n Size of file should be less than $max_allowed_file_size";
        }

        //------ Validate the file extension -----
        $allowed_ext = false;
        for ($i = 0; $i < sizeof($allowed_extensions); $i++)
        {
            if (strcasecmp($allowed_extensions[$i], $type_of_uploaded_file) == 0)
            {
                $allowed_ext = true;
            }
        }

        if (!$allowed_ext)
        {
            $errors .= "\n The uploaded file is not supported file type. " .
                    " Only the following file types are supported: " . implode(',', $allowed_extensions);
        }

        //copy 
        $path_to_uploaded_file = $this->upload_folder . $uploadedFileName;
        if (is_uploaded_file($uploadedTmpName))
        {
            if (!copy($uploadedTmpName, $path_to_uploaded_file))
            {
                $errors .= '\n error while copying the uploaded file';
            }
        }
        return $errors;
    }

    function exitOnSuspicious()
    {
        $flag = 0;
        if (strlen($_POST['message']) > 1024)
            exit;

        if ($_SESSION['spam'] != 'didUseForm')
            exit;

        if (strlen($_POST['toName']) > 32 || strstr($_POST['toName'], '\n') || strstr($_POST['toName'], '\r'))
            exit;

        if (strlen($_POST['fromname']) > 32 || strstr($_POST['fromname'], '\n') || strstr($_POST['fromname'], '\r'))
            exit;


        if(stristr($_POST['subject'],"seo"))
           exit;
        // the following--an @ in the toName--is not what could from the mkToSelect function,
        // which suggests a hackign attempt.
        //
        if (@preg_match_all('@', $_POST['toName']) > 1)
            exit;

        if (strstr($_POST['message'], 'href=') || strstr($_POST['message'], 'url=') || strstr($_POST['message'], 'link='))
            $flag = 1;

        if ($flag == 1)
        {
            header("location: http://nsa.gov");
        }
    }

    function handlePost()
    {
        $ret = $errors = '';

        //while (list($k,$v)=each($_POST)) { echo "$k p= $v <br/>"; }

        $this->exitOnSuspicious();

        $proceed = FALSE;
        if (in_array($_POST['toName'], $this->keys))
            $proceed = TRUE;

        if ($proceed)
        {
            if (!isset($_POST['fromaddress']) || $_POST['fromaddress'] == null || strlen($_POST['fromaddress']) < 2)
            {
                //$ret .= '<h1 style="clear: both;">Your return email address is required!</h1>';
                $ret .= '<h1>Your return email address is required!</h1>';
                $ret .= '<h2><a href="' . $_SERVER['PHP_SELF'] . '?layout=contactus" >Try again......</a></h2>';
            }
            else
            {
                $message = "mailTo: " . wordwrap($_POST['fromaddress'] . "\n" . $_POST['message']);
                $toAddress = $this->names[$_POST['toName']];
                $ret = mail($toAddress, $_POST['subject'], $message);
            }
        }
        $ret = "<b>To:</b> " . $toAddress . "<br/>";
        $ret .= "<b>Subject: </b>" . $_POST['subject'] . "<br/>";
        $ret .= "<b>Message: </b>" . $_POST['message'] . "<br/>";
        return $ret;
    }

}
?>
