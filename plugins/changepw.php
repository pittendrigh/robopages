<?php
@session_start();

include_once("plugin.php");
include_once('database/database.php');
include_once('database/user.php');

//ini_set('error_reporting',0);

class changepw extends plugin
{

    function getOutput($divid)
    {
        $address = $ret = "";

        $ret .= $this->handleTraffic();
        return ($ret);
    }

    function make_seed()
    {
        list($usec, $sec) = explode(' ', microtime());
        return (float) $sec + ((float) $usec * 100000);
    }

    function generate_key()
    {
        $symbols = array();
        for ($i = 0; $i < 10; $i++)
        {
            $symbols[$i] = $i;
        }

        $temp_key = "";

        mt_srand($this->make_seed());
        for ($i = 0; $i < 6; $i++)
        {
            $x = mt_rand(0, 9);
            $dbg = $symbols[$x];
            $temp_key .= $dbg;
        }

        return $temp_key;
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
  <legend>Change Password Step One</legend>
    <p> If your email address already exists as part of a valid login, but you forgot or want to change your password: </p>
    <form action="?layout=changepw&amp;sendEmail"  method="post" enctype="multipart/form-data">
      <h2> Enter your email address <input type="text"  size="32" name="emailAddress"/></h2>
      <p> <input type="hidden" name="layout" value="changepw"/> 
      <p> <input type="hidden" name="sendEmail" value="sendEmail"/> 
      <input type="submit" name="submit" value="Send" />  
      <input type="button" name="cancel" value="Cancel" onclick='location.replace("http://$home")'; />
      </p>
    </form>
</fieldset>
ENDO;
        }
        else if (isset($_POST['sendEmail']))
        {
            $home = $_SERVER['HTTP_HOST'];
            $key = $this->generate_key();
            $_SESSION['keyway'] = $key;
            //echo "key: ", $key, "<br/>"; 
            $emailAddress = $_POST['emailAddress'];
            $ret .= <<<ENDO
<fieldset>
  <legend>Change Password Step Two</legend>
     <h2> Keep this window open. Use another tab or window to check your email</h2>
     <p> Cut and paste the confirmation code inside your last email (from MRB) into the confirmation code form below</p>
    <form action="$self?layout=changepw" onsubmit="" method="post" enctype="multipart/form-data">
      <p>Change Password Confirmation Code <input type="text"  size="8" name="confirmationCode"/></p>
      <p>&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; &nbsp;New Password &nbsp; <input type="text"  size="16" name="pw1"/></p>
      <p>New Password Again &nbsp; <input type="text"  size="16" name="pw2"/></p>
      <p> <input type="hidden" name="layout" value="changepw"/> 
      <p> <input type="hidden" name="updateDB" value="updateDB"/> 
      <p> <input type="hidden" name="emailAddress" value="$emailAddress"/> 
      Do not click the following submit button before you have entered the confirmation number from your inpbox
      <input type="submit" name="submit" value="Change Password"/>  
      <input type="button" name="back" value="Back" onclick="window.history.back()"/>
      <input type="button" name="cancel" value="Cancel" onclick='location.replace("http://$home")'; />
      </p>
    </form>
</fieldset>
ENDO;
            $this->sendMail();
        }
        else if (isset($_POST['updateDB']))
        {
            $ret = '';
            /* `
              $ret .= $_POST['emailAddress'] . "<br/>";
              $ret .= $_POST['emailAddress'] . "<br/>";
              $ret .= $_SESSION['keyway'] . "<br/>";
              $ret .= $_POST['confirmationCode'] . "<br/><br/>";
             */

            if (isset($_POST['confirmationCode']) && $_POST['confirmationCode'] == $_SESSION['keyway'])
            {
                if (isset($_POST['pw1']) && isset($_POST['pw2']) && $_POST['pw2'] == $_POST['pw1'])
                {
                    $test = new_password($_POST['emailAddress'], $_POST['pw1']);
                    if ($test)
                        $ret .= "Pasword udpated!";
                }
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
