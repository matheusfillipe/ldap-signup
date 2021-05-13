<?php
function generateRandomString($length = 96)
{
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function getClientIP(): string
{
    $keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
    foreach ($keys as $k) {
        if (!empty($_SERVER[$k]) && filter_var($_SERVER[$k], FILTER_VALIDATE_IP)) {
            return $_SERVER[$k];
        }
    }
    return false;
}

function format(string $string, array $values)
{
    foreach ($values as $key => $value) {
        $string = str_replace("{{{$key}}}", $value, $string);
    }
    return $string;
}

function template_path(string $lang_cc = null){
    include "config.php";
    if (isset($_SESSION["cc"]))
        $lang_cc = $GLOBALS["cc"];
    if (isset($GLOBALS["cc"]))
        $lang_cc = $GLOBALS["cc"];
    if ($lang_cc)
        $INCLUDE_STRINGS_PATH = "templates_".$lang_cc;
    else
        $INCLUDE_STRINGS_PATH = "templates";

    if (isset($lang_cc) && !empty($lang_cc)) $TEMPLATE = $INCLUDE_STRINGS_PATH."/";
    else $TEMPLATE = "templates/";

    include $TEMPLATE.'strings.php';

    if(!isset($RUNTIME_ERROR)){
        include_once 'templates/strings.php';
        echo $RUNTIME_ERROR->not_found;
        echo "<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>";
        echo format($RUNTIME_ERROR->template_not_found, ["template"=>$INCLUDE_STRINGS_PATH, "langcc"=>$LANG_CC]);
        die();
    }

    return $TEMPLATE;
}
