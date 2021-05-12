<?php
require_once 'vendor/autoload.php';
include_once 'config.php';
include_once 'redis.php';
include_once 'utils.php';

if (!$DEBUG)    error_reporting(0);
session_start();


use Gregwar\Captcha\PhraseBuilder;


function register_page($error=false){
    include 'config.php';
    if ($error)
        include 'html/error.htm';
    $_SESSION["captcha_token"] = generateRandomString(12);
    include "html/register.htm";
    echo '
    <script>
        const reload_captcha = async (e) => {
            var cont = document.getElementById("reload_captcha");
            cont.innerHTML = "<div class=\'spinner-border text-info\' role=\'status\'><span class=\'sr-only\'>Loading...</span></div>";
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
    unset($_SESSION['captcha_token']);
    include "config.php";
    $error = "";
    if (ldap_user_count($user->user_name)) {
        $error = $error."This username is already in use! Please choose another username<br>";
        unset($_POST["username"]);
    }
    if (preg_match("/\s/", $user->user_name)) {
        $error = $error."Username cannot contain whitespaces<br>";
        unset($_POST["username"]);
    }
    if (strlen($user->user_name) > $VAL_USER->max_username) {
        $error = $error."Username has to be smaller than ".($VAL_USER->max_username+1)." characters<br>";
        unset($_POST["username"]);
    }
    if (strlen($user->user_name) < $VAL_USER->min_username) {
        $error = $error."Username has to be bigger than ".($VAL_USER->min_username-1)." characters<br>";
        unset($_POST["username"]);
    }
    if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/',$user->user_name)) {
        $error = $error."The username cannot contain special characters<br>";
        unset($_POST["username"]);
    }
    if (preg_match('/^\d/',$user->user_name)) {
        $error = $error."The username cannot begin with a number<br>";
        unset($_POST["username"]);
    }
    include "blacklists/usernames.php";
    if(in_array($user->user_name, $USERNAME_BLACKLIST)) {
        $error = $error."That Username is not allowed!<br>";
        unset($_POST["username"]);
    }


    if (preg_match("/\s/", $user->name)) {
        $error = $error."First Name cannot contain whitespaces<br>";
        unset($_POST["name"]);
    }
    if (strlen($user->name) > $VAL_USER->max_first_name) {
        $error = $error."First Name has to be smaller than ".($VAL_USER->max_first_name+1)." characters<br>";
        unset($_POST["name"]);
    }
    if (strlen($user->name) < $VAL_USER->min_first_name) {
        $error = $error."First Name has to be bigger than ".($VAL_USER->min_first_name-1)." characters<br>";
        unset($_POST["name"]);
    }
    if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\0-9]/',$user->name)) {
        $error = $error."The first name cannot contain special characters or numbers<br>";
        unset($_POST["name"]);
    }


    if (preg_match("/\s/", $user->last_name)) {
        $error = $error."Last Name cannot contain whitespaces<br>";
        unset($_POST["last_name"]);
    }
    if (strlen($user->last_name) > $VAL_USER->max_last_name) {
        $error = $error."Last Name has to be smaller than ".($VAL_USER->max_last_name+1)." characters<br>";
        unset($_POST["last_name"]);
    }
    if (strlen($user->last_name) < $VAL_USER->min_last_name) {
        $error = $error."Last Name has to be bigger than ".($VAL_USER->min_last_name-1)." characters<br>";
        unset($_POST["last_name"]);
    }
    if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\ 0-9]/',$user->last_name)) {
        $error = $error."The last name cannot contain special characters or numbers<br>";
        unset($_POST["last_name"]);
    }


    if (ldap_mail_count($user->email)) {
        $error = $error."This email is already has an account. Did you forget your password?<br>";
        unset($_POST["email"]);
    }
    if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $error = $error."Invalid email format<br>";
        unset($_POST["email"]);
    }elseif(in_array(explode("@", $user->email)[1], $MAIL_HOST_BLACKLIST )){
        $error = $error."This email service is not allowed<br>";
        unset($_POST["email"]);
    }
    $pending = redis_get("pending");
    if ($pending){
        $maillist = $pending->mails;
        if (in_array($user->email, $maillist)){
            $error = $error."This email is already pending approval, check your mailbox or try to register with a different email<br>";
            unset($_POST["email"]);
        }
    }


    if ($_POST["password"] != $_POST["password_confirm"]) {;
        $error = $error."Passwords do not match!<br>";
        unset($_POST["password_confirm"]);
    }
    $password = $_POST["password"];
    if (strlen($password) < $VAL_USER->min_password) {
        $error = $error."Password should have at least ".$VAL_USER->min_password." characters<br>";
        unset($_POST["password"]);
        unset($_POST["password_confirm"]);
    }
    if (strlen($password) > $VAL_USER->max_password) {
        $error = $error."Your password is too big!<br>";
        unset($_POST["password"]);
        unset($_POST["password_confirm"]);
    }
    include "blacklists/password.php";
    if(in_array($password, $PASSWORD_BLACKLIST)) {
        $error = $error."That password is not allowed!<br>";
        unset($_POST["password"]);
        unset($_POST["password_confirm"]);
    }
    foreach (array("username", "name", "last_name", "email") as &$field) {
        if (!isset($_POST[$field]))
            continue;
        $value = strtoupper($_POST[$field]);
        $PASSWORD = strtoupper($password);
        if(strpos($value, $PASSWORD) !== false || strpos($PASSWORD, $value) !== false){
            $error = $error."Your password cannot contain any of your names or email neither the names can contain the password<br>";
            unset($_POST["password"]);
            unset($_POST["password_confirm"]);
            break;
        }
    }

    if (!(isset($_SESSION['captcha']) && PhraseBuilder::comparePhrases($_SESSION['captcha'], $_POST['captcha']))) {
        $error = $error."Wrong captcha!<br>";
    }
    unset($_SESSION["captcha"]);
    
    return $error;
}

function approve_request($user){
    include "mail.php";
    echo "<h2>Almost there! Confirm your email</h2>";
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
    echo "<p>Didn't receive anything yet? <a href='".$BASE_URL."/?type=resend&token=".$_SESSION['resend']."'>Click here</a> to resend the confirmation email.</p>";
}


// PAGE
include "html/header.htm";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'ldap.php';
    if (isset($_POST['type'])) {
        switch ($_POST['type']) {
            case "register":
                $user = new User($_POST["username"], $_POST["name"], $_POST["last_name"], $_POST["email"], $_POST["password"]); 
                if (redis_inc_ipdata(getClientIP(), "register", true) > $HOURLY_REGISTRATIONS){
                    include "html/registration_limit.htm";
                }else{
                    $error = verify_request($user);
                    if ($error)
                        register_page($error);
                    else
                        approve_request($user);
                }
                break;
        }
    }
} elseif (isset($_GET['type'])) {
    switch ($_GET['type']) {
        case "confirmation":
            if (!isset($_GET["token"])){
                echo "INVALID REQUEST!";
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
                        echo "<h1>Email Confirmation</h1>";
                        include "html/mail_confirmed.htm";
                    }else{
                        echo "<h1>Email Confirmation</h1>";
                        include "html/registration_error.htm";
                    }
                    redis_delete($token);
                }else{
                    include "html/token_expired.htm";
                }
            }
            break;
        case "resend":
            if (isset($_GET['token']) && isset($_SESSION['resend']) && $_GET['token'] == $_SESSION['resend']){
                include "mail.php";
                include "html/resend_mail.htm";
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
    }


} else {
    unset($_SESSION['captcha_token']);
    register_page();
}

include "html/bottom.htm";
?>
