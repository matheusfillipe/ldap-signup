<?php
include_once 'ldap.php';
include_once 'redis.php';
include_once 'config.php';
include_once 'utils.php';

$TEMPLATE = template_path();

function validate_username(string $username)
{
        global $TEMPLATE;
        include 'config.php';
        include $TEMPLATE . 'strings.php';
        $error = "";
        if (ldap_user_count($username)) {
                $error = $error . $USERNAME_VALIDATION_ERROR->registered;
                unset($_POST["username"]);
        }
        if (preg_match("/\s/", $username)) {
                $error = $error . $USERNAME_VALIDATION_ERROR->no_whitespaces;
                unset($_POST["username"]);
        }
        if (strlen($username) > $VAL_USER->max_username) {
                echo $VAL_USER->max_username;
                echo $USERNAME_VALIDATION_ERROR->smaller_than;
                $error = $error . format($USERNAME_VALIDATION_ERROR->smaller_than, ["num" => $VAL_USER->max_username + 1]);
                echo $error;
                unset($_POST["username"]);
        }
        if (strlen($username) < $VAL_USER->min_username) {
                $error = $error . format($USERNAME_VALIDATION_ERROR->bigger_than, ["num" => $VAL_USER->min_username - 1]);
                unset($_POST["username"]);
        }
        if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\]/', $username)) {
                $error = $error . $USERNAME_VALIDATION_ERROR->no_special_chars;
                unset($_POST["username"]);
        }
        if (preg_match('/^\d/', $username)) {
                $error = $error . $USERNAME_VALIDATION_ERROR->no_number_begining;
                unset($_POST["username"]);
        }
        include "blacklists/usernames.php";
        if (in_array($username, $USERNAME_BLACKLIST)) {
                $error = $error . $USERNAME_VALIDATION_ERROR->blacklisted;
                unset($_POST["username"]);
        }
        return $error;
}

function validate_name(string $name, object $ERRORS)
{
        global $TEMPLATE;
        include "config.php";
        include $TEMPLATE . 'strings.php';
        $error = "";
        if (preg_match("/\s/", $name)) {
                $error = $error . $ERRORS->no_whitespaces;
                unset($_POST["name"]);
        }
        if (strlen($name) > $VAL_USER->max_first_name) {
                $error = $error . format($ERRORS->smaller_than, ["num" => $VAL_USER->max_first_name + 1]);
                unset($_POST["name"]);
        }
        if (strlen($name) < $VAL_USER->min_first_name) {
                $error = $error . format($ERRORS->bigger_than, ["num" => $VAL_USER->min_first_name - 1]);
                unset($_POST["name"]);
        }
        if (preg_match('/[\'\/~`\!@#\$%\^&\*\(\)_\-\+=\{\}\[\]\|;:"\<\>,\.\?\\\0-9]/', $name)) {
                $error = $error . $ERRORS->no_special_chars;
                unset($_POST["name"]);
        }
        return $error;
}

function validate_email(string $email)
{
        global $TEMPLATE;
        include "config.php";
        include $TEMPLATE . 'strings.php';
        $error = "";

        if (ldap_mail_count($email)) {
                $error = $error . format($EMAIL_VALIDATION_ERROR->registered, ["link" => $BASE_URL . "?type=recover"]);
                unset($_POST["email"]);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = $error . $EMAIL_VALIDATION_ERROR->invalid;
                unset($_POST["email"]);
        } elseif (in_array(explode("@", $email)[1], $MAIL_HOST_BLACKLIST)) {
                $error = $error . $EMAIL_VALIDATION_ERROR->blacklisted;
                unset($_POST["email"]);
        }
        $pending = redis_get("pending");
        if ($pending) {
                $maillist = $pending->mails;
                if (in_array($email, $maillist)) {
                        $error = $error . $EMAIL_VALIDATION_ERROR->pending;
                        unset($_POST["email"]);
                }
        }
        return $error;
}


function validate_password(string $password)
{
        global $TEMPLATE;
        include "config.php";
        include $TEMPLATE . 'strings.php';
        $error = "";
        if ($_POST["password"] != $_POST["password_confirm"]) {;
                $error = $error . $PASSWORD_VALIDATION_ERROR->no_match;
                unset($_POST["password_confirm"]);
        }
        if (strlen($password) < $VAL_USER->min_password) {
                $error = $error . format($PASSWORD_VALIDATION_ERROR->bigger_than, ["num" => $VAL_USER->min_password]);
                unset($_POST["password"]);
                unset($_POST["password_confirm"]);
        }
        if (strlen($password) > $VAL_USER->max_password) {
                $error = $error . format($PASSWORD_VALIDATION_ERROR->smaller_than, ["num" => $VAL_USER->max_password]);
                unset($_POST["password"]);
                unset($_POST["password_confirm"]);
        }
        include "blacklists/password.php";
        if (in_array($password, $PASSWORD_BLACKLIST)) {
                $error = $error . $PASSWORD_VALIDATION_ERROR->blacklisted;
                unset($_POST["password"]);
                unset($_POST["password_confirm"]);
        }
        foreach (array("username", "name", "last_name", "email") as &$field) {
                if (!isset($_POST[$field]))
                        continue;
                $value = strtoupper($_POST[$field]);
                $PASSWORD = strtoupper($password);
                if (strpos($value, $PASSWORD) !== false || strpos($PASSWORD, $value) !== false) {
                        $error = $error . $PASSWORD_VALIDATION_ERROR->shared_inclusion;
                        unset($_POST["password"]);
                        unset($_POST["password_confirm"]);
                        break;
                }
        }
        return $error;
}
