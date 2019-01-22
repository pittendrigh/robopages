<?php
@session_start();

include_once("plugin.php");
include_once('database/database.php');
include_once('database/user.php');

ini_set('error_reporting', 1);

class lost_login_name extends plugin
{
    protected $keys = array();
    protected $names = array();

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

        $self = $_SERVER['PHP_SELF'];
        $ret = '';
        $_SESSION['sapammy'] = 'didUseForm';

        if ($_SERVER['REQUEST_METHOD'] == 'GET')
        {
            $home = $_SERVER['HTTP_HOST'];
            $ret .= <<<ENDO
<fieldset>
  <legend>Lost Login Name</legend>
    <p> Your login name starts off as your email address.  But perhaps you changed it and forgot!</p>
    <p> So, if you purchased a valid login but cannot remember your login name</p>
    <form action="?layout=lost_login_name&amp;sendEmail"  method="post" enctype="multipart/form-data">
      <h2>The first lost login name step is your email address <input type="text"  size="32" name="emailAddress"/></h2>
      <p> <input type="hidden" name="layout" value="lost_login_name"/> 
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

            $ret = '';

            $login_name = user_name_by_email($_POST['emailAddress']);
            $message = <<< eENDO
Your current login name is: $login_name
eENDO;
            $this->sendMail($message);
            $ret = "Check your email -- if you entered a valid email address already known to us, you should have mail.<br/>";
        }
        return $ret;
    }

    function sendMail($message)
    {
        //$this->exitOnSuspicious();

        $subject = "Montana Riverboats Login Name\n";

        $ret = mail($_POST['emailAddress'], $subject, $message);
    }

}
?>
