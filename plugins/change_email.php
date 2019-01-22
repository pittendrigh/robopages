<?php
@session_start();

include_once("plugin.php");
include_once('database/database.php');
include_once('database/user.php');

//ini_set('error_reporting',0);

class change_email extends plugin
{

    function getOutput($divid)
    {
        $address = $ret = "";

        $ret .= $this->handleTraffic();
        return ($ret);
    }

    function handleTraffic()
    {

        //while(list($k,$v) = each($_GET)){ echo "$k g= $v<br/>";}
        //while(list($k,$v) = each($_POST)){ echo "$k p= $v<br/>";}
        $home = $_SERVER['HTTP_HOST'];
        $self = $_SERVER['PHP_SELF'];
        $ret = '';
        $_SESSION['sapammy'] = 'didUseForm';

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $ret .= <<<ENDO
<fieldset>
  <legend>Change Email Step One</legend>
    <form action="?layout=change_email&amp;checkitout"  method="post" enctype="multipart/form-data">
      <p> Current User Name <input type="text" size="32" name="user_name" /> </p>
      <p> Current Password <input type="text" size="32" name="ppassword" /> </p>
      <p> &nbsp; </p>
      <p> New Email <input type="text" size="32" name="change_email" /> </p>
      <p> New Email Again <input type="text" size="32" name="change_email" /> </p>
      <p> <input type="hidden" name="layout" value="change_email2"/> 
      <p> <input type="hidden" name="checkitout" value="checkitout"/> 
      <input type="submit" name="submit" value="Submit" />  
      <input type="button" name="cancel" value="Cancel" onclick='location.replace("http://$home")'; />
      </p>
    </form>
</fieldset>
ENDO;
        }
        else if (isset($_POST['checkitout']))
        {
            $ret = '';

            $user_id = user_login($_POST['user_name'], $_POST['ppassword']);
            if ($_POST['change_email2'] != $_POST['change_email'])
            {
                $ret = "The two email addresses do not match!<br/>";
                $ret .= '<a href="/?layout=change_email"> Try Again </a><br/>';
                $ret .= "<a href=" / "> Cancel </a><br/>";
            }
            else if ($user_id > 1)
            {
                $sql = "UPDATE user SET email='" . $_POST['change_email'] . "' WHERE user_id='" . $user_id . "'";
                //$ret .= $sql. "<br/>";
                $stmt = pdo_query($sql);
                if ($stmt)
                    $ret .= "New Email " . $_POST['change_email'] . " ready to go!";
            }
            else
            {
                $ret = "Something did not work right!<br/>";
                $ret .= '<a href="/?layout=change_email"> Try Again </a><br/>';
                $ret .= "<a href=" / "> Cancel </a><br/>";
            }
        }
        return $ret;
    }

    function sendMail()
    {
        //$this->exitOnSuspicious();
        //while (list($k,$v)=each($_POST)) { echo "$k p= $v <br/>"; }

        $subject = "Montana Riverboats Change Password Information\n";
        $subject = "Cut and paste the following verification code number into the form you left open in another tab or window\n";
        $message = "Login name: " . $_POST['emailAddress'] . "\n";
        $message .= "Required Key Code: " . $_SESSION['keyway'] . "\n";

        $ret = mail($_POST['emailAddress'], $subject, $message);
    }

}
?>
