<?php
   //Connecting to Redis server on localhost 
   function connect(){
      include 'config.php';
      $redis = new Redis(); 
      $redis->connect('127.0.0.1', 6379); 
      $redis->auth("$REDIS_PASS");
      return $redis;
   }
   function redis_get($key){
      $redis = connect();
      return json_decode($redis->get($key));
   }
   function redis_set($key, $data, $timeout=null){
      $redis = connect();
      $redis->set($key, json_encode($data), $timeout);
   }

   function redis_inc_ipdata($ip, $attr, $get=false){
      $count = redis_get($ip);
      if ($count){
         if (isset($count->$attr))  $count->$attr = $count->$attr+1;
         else  $count->$attr = 1;
      }else $count = (object)[$attr=>1];

      if (!$get) redis_set($ip, $count, 3600);
      return $count->$attr;
   }

   function redis_delete($key){
      $redis = connect();
      $redis->del($key);
   }
