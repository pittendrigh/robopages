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
        $ret = '';
        $ret .= '<p><b>Note: </b>
             login names and passwords are case sensitive: 
             Robert is not the same as robert.</p>';
        $ret .= '<form action="?layout=authUtils" method="post">
       <p><b>login name </b>  
       <input type="text" name="username" value="" size="32" maxlength="48" > </p> 
       <p><b>password</b>  
       <input type="password" name="password" value="" size="12" maxlength="32" > </p>';
        $ret .= '<input type="hidden" name="mode" value="processLoginForm">';
        $ret .= '<p><input type="submit" name="submit" value="login"></p> ';
        $ret .= '</form></fieldset>';
        $ret .= '<p><a href="' . dirname($_SERVER['PHP_SELF']) . '?mode=logout"><b>cancel</b></a></p>';
        return $ret;
    }

    function processLoginForm()
    {
        $ret = '';

        //if (isset($_POST['username']))
        if ($_SERVER['REQUEST_METHOD'] == 'POST')
        {
            $this->userlogin($_POST['username'], $_POST['password']);
            if (StaticRoboUtils::isAdmin())
            {
                //echo "wasAdmin ", $this->selfUrl, "<br/>";
                $ret = <<<ENDO
              <button><a href="?layout=nerd">Logged in! </a></button> <-- to the admin screen
ENDO;
                return $ret;
             } else return "foogow<br/>";
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
