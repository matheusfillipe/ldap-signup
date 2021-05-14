<?php
include 'redis.php';
include 'utils.php';
include 'config.php';
$token = generateRandomString($REG_TOKEN_LEN);
redis_set($token, (object)["valid"=>true]);
echo $token."\n";
