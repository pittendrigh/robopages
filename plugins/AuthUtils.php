<?php

include_once("conf/globals.php");
include_once("domDrone.class.php");
include_once("plugin.php");

class AuthUtils extends plugin
{
    protected $passwordFile = "plugins/RobopagePasswds.php";
    protected $users;

    
      function __construct($returnUrl = null)
      {
      unset($_SESSION['mode']);
      $this->init();
      //$this->traffic();
      }
     

    function logout()
    {
        //echo "Auth logout<br/>";
        StaticRoboUtils::chmod_r($_SESSION['prgrmDocRoot'], 0555);
        unset($_SESSION['isLoggedIn']);
        unset($_SESSION['username']);
        unset($_SESSION['mode']);
        //session_destroy();
    }

function check_hash($pass,$hash)
{
    $ret=FALSE;
      if (password_verify($pass, $hash)) 
        $ret=TRUE;
    return $ret;
}      

    function userlogin($username, $password)
    {
        global $sys_robosauce;

        $robosauce = $sys_robosauce;
        $password .= $robosauce;
        $ret = FALSE;
        $usernames = array();
        $privileges = array();
        $customdirs = array();
        $hashlines = file($this->passwordFile);
        $hashlinecnt = count($hashlines);
        for ($i = 0; $i < $hashlinecnt; $i++)
        {
            $tokens = explode(":", trim($hashlines[$i]));
            $namekey = trim($tokens[0]);
            $hash = trim($tokens[1]);
            $privilege = trim($tokens[2]);

            $usernames[$namekey] = $hash;
            $privileges[$namekey] = $privilege;
        }

        if($this->check_hash($password,$hash))
        {
            //echo $username, ".......<br/>";
            $_SESSION['username'] = $username;
            $_SESSION['isLoggedIn'] = TRUE;
            $_SESSION['privilege'] = $privileges[$username];
            $ret = TRUE;
        }

        return $ret;
    }
/*
    static function isAdmin()
    {
        $ret = FALSE;
        if (
               (isset($_SESSION['privilege']) && isset($_SESSION['isLoggedIn']))
               &&
               ($_SESSION['privilege'] == 'nimda' && $_SESSION['isLoggedIn'] == TRUE)
           )
               
        {
            $ret = TRUE;
        }
        return $ret;
    }
*/

    function showLoginForm()
    {
        global $sys_not_configed_yet;

        $currentDirUrl = $_SESSION['currentDirUrl'];
        $ret = '';
        $ret .= '<p><b>Note: </b>
             login names and passwords are case sensitive: 
             Robert is not the same as robert.</p>';
        $ret .= '<form action="?robopage='.$currentDirUrl.'l&amp;layout=authUtils" method="post">
       <p><b>login name </b>  
       <input type="text" name="username" value="" size="32" maxlength="48" > </p> 
       <p><b>password</b>  
       <input type="password" name="password" value="" size="12" maxlength="32" > </p>';
        $ret .= '<input type="hidden" name="mode" value="processLoginForm">';
        $ret .= '<p><input type="submit" name="submit" value="login"></p> ';
        $ret .= '</form></fieldset>';
        $ret .= '<p><a href="' . dirname($_SERVER['PHP_SELF']) . '?mode=logout"><b>cancel</b></a></p>';

if(isset($sys_not_configed_yet) && $sys_not_configed_yet == TRUE)
{
  $ret .= <<<ENDO
<fieldset style="width: 80%; padding: 1em;"><legend> <b>First Time User NOTE:</b> </legend> To get rid of this message edit <b>conf/globals.php</b>
change <b>\$sys_not_configed_yet</b> from TRUE to FALSE, or simply delete that line.
<br/><br/>
To log in to the administrative interface (?layout=nerd)
use login name: <b>gitgit</b>
password: <b>gotgot</b>
<br/><br/>

To change or delete that login/password pair (gitgit/gotgot) read the Documentation on passwords.
You will need to use <b>commandLineUtils/phppw 'password' </b> and save that output,
which gets used in <b>plugins/RobopagePasswds.php</b>
<br/><br/>

If you simply want to disable the admin gui do this:
<b style="color: magenta;">cat /dev/null > plugins/RobopagePasswds.php</b>
<br/><br/>

<b>Note too: </b> To do this properly you should edit both <b>conf/globals.php</b>
 and the text of <b>commandLineUtils/phppw</b> so
<b>\$robosauce='not_the_given_default'; </b> and so <b>\$robosauce</b> is the same in both files.
<br/><br/>
Getting the admin interface up and running is probably not something for beginners to attempt.  And XTerminal keyboard hackers
don&apos;t need it. But the Admin Interface does have a valid niche to fill.  I made the Admin Interface so one of my customers could administer her own website--after I got it set up and running for her.  
</fieldset>
ENDO;

}
 
        return $ret;
    }

    function processLoginForm()
    {
        $ret = '';

        $currentDirUrl = $_SESSION['currentDirUrl'];
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $this->userlogin($_POST['username'], $_POST['password']);
            if (StaticRoboUtils::isAdmin())
            {
                //echo "wasAdmin ", $this->selfUrl, "<br/>";
                $ret = <<<ENDO
              <a class="button" href="?robopage=$currentDirUrl&amp;layout=nerd">Logged in! </a> &nbsp; <-- to the admin screen
ENDO;
                return $ret;
             } else return "Error<br/>";
        }
        else
        {
            return $this->showLoginForm();
        }
    }

    function getOutput($divid)
    {

        if (isset($_GET['logout']))
            $this->logout();
        else if (isset($_POST['mode']) && $_POST['mode'] == 'processLoginForm')
        {
            return $this->processLoginForm();
        }
        else
        {
            StaticRoboUtils::chmod_r($_SESSION['prgrmDocRoot'], 0555);
            unset($_SESSION['isLoggedIn']);
            unset($_SESSION['privilege']);
            return($this->showLoginForm());
        }
    }

}
?>
