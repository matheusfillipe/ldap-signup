<?php

function debug($msg) {
        include 'config.php';
        if ($DEBUG)
                echo $msg."\n";
}
function generateSalt($length=10) {
  $chars="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

  $string="";
  for($i=0;$i<$length;$i++) {
    $string.=substr($chars,rand(0,strlen($chars)-1),1);
  }

  return $string;
}

class User {
        function __construct(string $user_name, string $first_name, string $last_name, string $email, string $password){
                $this->user_name = $user_name;
                $this->name = $first_name;
                $this->first_name = $first_name;
                $this->last_name = $last_name;
                $this->email = $email;
                $this->user_hash = "{crypt}" . crypt($password,'$6$'.generateSalt(10).'$');
                $this->password = $this->user_hash;
        }
}

function ldap_search_query($query, $filter="cn"){
        include 'config.php';
        $ldap_host = $HOST;
        $ldap_port = $PORT;
        $ldaptree = explode("{},", $BASE_DN)[1];

        $ldap_user = "cn=".$USER.",".join(",", array_slice(explode(",", $ldaptree), 1));
        $ldap_pass = $PASSWORD;

        //First: Connect to  LDAP Server
        $connect = ldap_connect( $ldap_host, $ldap_port)
         or debug(">>Could not connect to LDAP server to add user<<");
        ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);

        //Login to LDAP
        ldap_bind($connect, $ldap_user, $ldap_pass)
           or debug(">>Could not bind to $ldap_host to add user<<");

        
        $result = ldap_search($connect,$ldaptree, "(".$filter."=".$query.")") or die ("Error in search query: ".ldap_error($connect));
        $data = ldap_get_entries($connect, $result);
        return $data;
}

function ldap_add_user ($user)
{
        include 'config.php';
        $ldap_host = $HOST;
        $ldap_port = $PORT;
        $base_dn = str_replace('{}', $user->user_name, $BASE_DN);
        $ldaptree = explode("{},", $BASE_DN)[1];


        $info["givenName"]=$user->first_name;
        $info["sn"]=$user->last_name;
        $info["uid"]=$user->user_name;
        #$info["homeDirectory"]="/home/";
        $info["mail"]=$user->email;
        $info["displayName"]= $user->first_name." ".$user->last_name;
        #$info["departmentNumber"]=$user->id;
        $info["cn"] =$user->user_name;
        $info["userPassword"]=$user->user_hash;
        $info["objectclass"][0] = "top";
        $info["objectclass"][1] = "person";
        $info["objectclass"][2] = "inetOrgPerson";
        $info["objectclass"][3] = "organizationalPerson";



        $ldap_user = "cn=".$USER.",".join(",", array_slice(explode(",", $ldaptree), 1));
        $ldap_pass = $PASSWORD;

        //First: Connect to  LDAP Server
        $connect = ldap_connect( $ldap_host, $ldap_port)
         or debug(">>Could not connect to LDAP server to add user<<");
        ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);

        //Login to LDAP
        ldap_bind($connect, $ldap_user, $ldap_pass)
           or debug(">>Could not bind to $ldap_host to add user<<");

        // Adding new user

    $add = ldap_add($connect, $base_dn, $info)
      or debug(">>Not able to load user <<");

        // Close connection
       ldap_close($connect);

   // Return value of operation

        return $add;
}
function ldap_user_count($user){
        return ldap_search_query($user)["count"];
}
function ldap_mail_count($email){
        return ldap_search_query($email, "mail")["count"];
}
?>
