<?php

@session_start();

class orderform {

    protected $parent;

    function __construct() {
        
    }

    function setParent($parent) {
        $this->parent = $parent;
    }

    function getOutput() {
       $ret=$msg='';

       $msgFile = dirname($_SESSION['prgrmDocRoot']) . '/plugins/orderformMessage.txt';
       if(@stat($msgFile))
       {
          $msg .= file_get_contents($msgFile); 
          //echo htmlentities($msg), "<br/>";
       }


        $ret .= <<<ENDO

  <form style="text-align: center; margin: 0 auto;" name="PayPal" action="https://www.paypal.com/cgi-bin/webscr" method="post">

    <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but22.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
    <input type="hidden" name="add" value="1"/>
    <input type="hidden" name="cmd" value="_cart"/>
    <input type="hidden" name="business" value="sandy.pittendrigh@gmail.com"/>
    <input type="hidden" name="item_name" value="mrblogin"/>
    <input type="hidden" name="item_number" value="1"/>
    <input type="hidden" name="amount" value="25.00"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="currency_code" value="USD"/>

    <input type="image" src="/systemimages/paypal-icon.jpg" onclick="window.forms['PayPal'].submit();"/> 
    </form> 
  <h2 style="text-align: center; color: #770000">Online Boat Building Plans/Instructions: $25.00</h2>
    <h2 style="text-align: center;">Complete step-by-step written instructions plus material lists and material suppliers</h2>

  If you purchase an online subscription a password will be sent
to you via email. 

The password grants access to otherwise hidden folders of photographs and online blueprint-like diagrams plus <i>complete, clearly written step-by-step building instructions</i>. Once you have a MRB password, you can return to the private blueprint pages whenever you want. You can study these drawings online or print them directly from your browser. <b>A twenty five dollar subscription grants access to <span style="color: #880000;">all </span>Montana Riverboats hull designs: currently the Honky Dory, Buffalo Boat and Beavertail. </b> With this subscription you'll also learn how to convert anybody else's wooden drift boat plans to wood-fiberglass stitch and glue construction.
<br />
<br />
If you do purchase the plans and have trouble with your password, send me an <a class="stuff" href="?layout=contactus"> email </a>. If you purchase plans and the password works--but you don't like what you see--<b>ask for a refund.</b> I'm easy. I like happy customers. I don't need a reason for a refund. Just a written request. Thank you! <br/>


  <div style="border: 3px outset #ffaaaa; width: 25em; background: #ffffff; padding: 1em; margin: 1em;"><b style="color: #366666; font-size: 18"> $25.00 PayPal or Mastercard or Visa:</b>
 


  <form name="PayPal" action="https://www.paypal.com/cgi-bin/webscr" method="post">

    <input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but22.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!"/>
    <input type="hidden" name="add" value="1"/>
    <input type="hidden" name="cmd" value="_cart"/>
    <input type="hidden" name="business" value="sandy.pittendrigh@gmail.com"/>
    <input type="hidden" name="item_name" value="mrblogin"/>
    <input type="hidden" name="item_number" value="1"/>
    <input type="hidden" name="amount" value="25.00"/>
    <input type="hidden" name="no_note" value="1"/>
    <input type="hidden" name="currency_code" value="USD"/>

    <input type="image" src="/systemimages/paypal-icon.jpg" onclick="window.forms['PayPal'].submit();"/> 
    </form> 

  </div>
 
<font size="+1"><b>Montana Riverboats</b>
<br /> 118 Erik Drive
<br /> Bozeman, MT, 59715  U.S.A.</font>
<br />Contact MRB: <a class="stuff" href="?layout=contactus"><font size="+1">email at montana-riverboats.com</font></a>

ENDO;
        return ($msg . $ret);
    }

}

?>
