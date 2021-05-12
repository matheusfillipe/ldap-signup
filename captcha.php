<?php
require_once 'vendor/autoload.php';
require_once 'config.php';
require_once 'redis.php';
include_once 'utils.php';
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

session_start();
error_reporting(0);

if (isset($_SESSION['captcha_token']) && $_GET['token']==$_SESSION['captcha_token']) {
        header('Content-type: image/jpeg');
        header('Cache-control: no-store');
        if (redis_inc_ipdata(getClientIP(), "captcha") > $HOURLY_CAPTCHAS) die("blocked");
        $phraseBuilder = new PhraseBuilder($CAPTCHA_LENGTH);
        $builder = new CaptchaBuilder(null, $phraseBuilder);
        $builder->setDistortion(1);
        if (isset($SIMPLECAPTCHA) && $SIMPLECAPTCHA) $builder->build(250, 40);
        else $builder->buildAgainstOCR(250, 40);
        $_SESSION['captcha'] = $builder->getPhrase();
        $builder->output();
}else echo "huh?";
