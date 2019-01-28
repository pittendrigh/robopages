<?php
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
     

/*
    function traffic()
    {
        if (isset($_GET['logout']))
            $this->logout();
        else if (isset($_POST['mode']) && $_POST['mode'] == 'processLoginForm')
        {
            $this->processLoginForm();
        }
        else
        {
            unset($_SESSION['isLoggedIn']);
            unset($_SESSION['privilege']);
            return($this->showLoginForm());
        }
    }
*/

    function logout()
    {
        //echo "Auth logout<br/>";
        StaticRoboUtils::chmod_r($_SESSION['prgrmDocRoot'], 0555);
        unset($_SESSION['isLoggedIn']);
        unset($_SESSION['username']);
        unset($_SESSION['mode']);
        //session_destroy();
    }


    function userlogin($username, $password)
    {
        $ret = FALSE;
        $usernames = array();
        $privileges = array();
        $customdirs = array();
        $passwdlines = file($this->passwordFile);
        $passwdlinecnt = count($passwdlines);
        for ($i = 0; $i < $passwdlinecnt; $i++)
        {
            $tokens = explode(":", trim($passwdlines[$i]));
            $namekey = trim($tokens[0]);
            $passwd = trim($tokens[1]);
            $privilege = trim($tokens[2]);

            $usernames[$namekey] = $passwd;
            $privileges[$namekey] = $privilege;
        }

        if ($usernames[$username] == $password)
        {
            //echo $username, ".......<br/>";
            $_SESSION['username'] = $username;
            $_SESSION['isLoggedIn'] = TRUE;
            $_SESSION['privilege'] = $privileges[$username];
            $ret = TRUE;
        }

        return $ret;
    }

    static function isAdmin()
    {
        $ret = FALSE;
        //echo "isAdmin ", $_SESSION['privilege'], "<br/>";
        if (isset($_SESSION['privilege']) && $_SESSION['privilege'] == 'nimda')
        {
            $ret = TRUE;
        }
        return $ret;
    }

    function showLoginForm()
    {
        $ret = '';
        $ret .= '<p><b>Note: </b>
             login names and passwords are case sensitive: 
             Robert is not the same as robert.</p>';
        $ret .= '<form action="?layout=auth" method="post">
       <p><b>login name </b>  
       <input type="text" name="username" value="" size="32" maxlength="48" > </p> 
       <p><b>password</b>  
       <input type="password" name="password" value="" size="12" maxlength="12" > </p>';
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
            $this->userlogin($_POST['username'], md5($_POST['password']));
            if ($this->isAdmin())
            {
                //echo "wasAdmin ", $this->selfUrl, "<br/>";
                $ret = <<<ENDO
              <button><a href="?layout=nerd">Logged in! </a></button> <-- to the admin screen
ENDO;
                return $ret;
            }
        }
        else
        {
            return $this->showLoginForm();
        }
    }

    function getOutput($divid)
    {
//        return $this->traffic();

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
