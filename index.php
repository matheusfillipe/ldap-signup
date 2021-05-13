<?php
require_once 'vendor/autoload.php';
include_once 'config.php';
include_once 'redis.php';
include_once 'utils.php';

if (!$DEBUG)    error_reporting(0);
else error_reporting(1);
session_start();

use Gregwar\Captcha\PhraseBuilder;

$URI = array_slice(explode("/", explode('?', $_SERVER['REQUEST_URI'], 2)[0]), -1)[0];
if (strlen($URI) == 2){
    $GLOBALS["cc"] = $URI;
    $_SESSION["cc"] = $URI;
}

if (isset($_GET["lang"])){
    $GLOBALS["cc"] = $_GET["lang"];
    $_SESSION["cc"] = $_GET["lang"];
}

$TEMPLATE = template_path();



function register_page($error=false){
    $TEMPLATE = template_path();
    include 'config.php';
    if ($error)
        include $TEMPLATE.'error.htm';
    $_SESSION["captcha_token"] = generateRandomString(12);
    include $TEMPLATE."register.htm";
    echo '
    <script>
        const reload_captcha = async (e) => {
            var cont = document.getElementById("reload_captcha");
            cont.innerHTML = "<div class=\'spinner-border text-info\' role=\'status\'><span class=\'sr-only\'>'.$STRINGS->reloading_captcha.'</span></div>";
            var img = document.getElementById("captcha")
            var url =  "'.$BASE_URL.'/captcha.php?token='.$_SESSION["captcha_token"].'"
            await fetch(url, { cache: "reload", mode: "no-cors" })
             .then(() => {
                img.src = url+"&t=" + new Date().getTime();
                setTimeout( () => {
                    cont.innerHTML = "<button id=\'reload\' class=\'btn btn-outline-info\' type=\'button\'> <span class=\'glyphicon glyphicon-refresh\' aria-hidden=\'true\'></span></button>";
                    bindButton()
                }, 500);
            })
        }
        function bindButton(){
            var button = document.getElementById("reload");
            button.addEventListener("click", reload_captcha)
        }
        bindButton()
    </script>
    ';
}


function verify_request($user){
    $TEMPLATE = template_path();
    unset($_SESSION['captcha_token']);
    include 'validators.php';
    include $TEMPLATE.'strings.php';
    $password = $_POST["password"];
    $error = "";

    $error .= validate_username($user->user_name);
    $error .= validate_name($user->name, $FIRST_NAME_VALIDATION_ERROR);
    $error .= validate_name($user->last_name, $LAST_NAME_VALIDATION_ERROR);
    $error .= validate_email($user->email);
    $error .= validate_password($password);


    if (!(isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $_POST['captcha']))) {
        $error = $error."Wrong captcha!<br>";
    }
    unset($_SESSION["captcha"]);
    
    return $error;
}

function approve_request($user){
    include "mail.php";
    $token = generateRandomString();
    redis_set($token, $user, $MAIL_CONFIRMATION_AWAIT_DELAY);
    $pending = redis_get("pending");
    if ($pending){
        $maillist = $pending->mails;
        array_push($maillist, $user->email);
    }
    else
        $maillist = [$user->email];
    redis_set("pending", (object)["mails"=>$maillist], $MAIL_CONFIRMATION_AWAIT_DELAY);

    $url = $BASE_URL."?type=confirmation&token=".$token;
    if (in_array(explode("@", $user->email)[1], $MAIL_HOST_DIRECT_FALLBACK))
        $smtp = $FALLBACK_SMTP;
    else
        $smtp = $SMTP;
    send_mail($user->email, $smtp, (object) [
        "subject" => $MAIL_TEMPLATE->subject, 
        "text" => str_replace("{{url}}", $url, $MAIL_TEMPLATE->text),
        "html" => str_replace("{{url}}", $url, $MAIL_TEMPLATE->html)
    ]);
    $_SESSION['resend'] = generateRandomString(12);
    $_SESSION['token'] = $token;
    $_SESSION['email'] = $user->email;
    $TEMPLATE = template_path();
    include $TEMPLATE."confirm_your_email.htm";
}


// PAGE
include $TEMPLATE."header.htm";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'ldap.php';
    if (isset($_POST['type'])) {
        switch ($_POST['type']) {
            case "register":
                $user = new User($_POST["username"], $_POST["name"], $_POST["last_name"], $_POST["email"], $_POST["password"]); 
                if (redis_inc_ipdata(getClientIP(), "register", true) > $HOURLY_REGISTRATIONS){
                    include $TEMPLATE."registration_limit.htm";
                }else{
                    $error = verify_request($user);
                    if ($error)
                        register_page($error);
                    else
                        approve_request($user);
                }
                break;
            case "recover":
                break;
        }
    }
} elseif (isset($_GET['type'])) {
    switch ($_GET['type']) {
        case "confirmation":
            if (!isset($_GET["token"])){
                echo $RUNTIME_ERROR->user_trying_invalid_get;
            }else{
                include "ldap.php";
                $token = $_GET["token"];
                $user = redis_get($token);
                if ($user){
                    if (ldap_add_user($user)){
                        if ($REDIRECT_TO)
                            header( "refresh:5;url=".$REDIRECT_TO);

                        $pending = redis_get("pending");
                        if ($pending){
                            $maillist = $pending->mails;
                            if (in_array($user->email, $maillist)){
                                unset($maillist[array_search($user->email, $maillist)]);
                                redis_set("pending", (object)["mails"=>$maillist], $MAIL_CONFIRMATION_AWAIT_DELAY);
                            }
                        }
                        redis_inc_ipdata(getClientIP(), "register");
                        echo $STRINGS->email_confirmation;
                        include $TEMPLATE."mail_confirmed.htm";
                    }else{
                        echo $STRINGS->email_confirmation;
                        include $TEMPLATE."registration_error.htm";
                    }
                    redis_delete($token);
                }else{
                    include $TEMPLATE."token_expired.htm";
                }
            }
            break;
        case "resend":
            if (isset($_GET['token']) && isset($_SESSION['resend']) && $_GET['token'] == $_SESSION['resend']){
                include "mail.php";
                include $TEMPLATE."resend_mail.htm";
                $token = $_SESSION['token'];
                $url = $BASE_URL."?type=confirmation&token=".$token;
                $smtp = $FALLBACK_SMTP;
                $mail = $_SESSION["email"];
                send_mail($mail, $smtp, (object) [
                    "subject" => $MAIL_TEMPLATE->subject, 
                    "text" => str_replace("{{url}}", $url, $MAIL_TEMPLATE->text),
                    "html" => str_replace("{{url}}", $url, $MAIL_TEMPLATE->html)
                ]);
                unset($_SESSION['resend']);
                unset($_SESSION['token']);
                unset($_SESSION['email']);
            }
            break;
        case "recover":
            break;
        case "confirm_recover":
            break;
    }


} else {
    unset($_SESSION['captcha_token']);
    register_page();
}

include $TEMPLATE."bottom.htm";
