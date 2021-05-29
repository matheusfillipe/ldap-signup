# LDAP SIGNUP

This is a simple registration script for ldap written fully in php that will
allow you to have a registration page that does all the form validation, mail
confirmation and allowing some basic configuration.

## Registration process

This branch also requires a secret token for registration that can be generated with: `php create_token.php`

1. The user fill the forms and the captcha
2. All the input data is validated on the backend. There are blacklists for
   email hosts, usernames, passwords. There are basic filters like not starting
   usernames with numbers, not include special characters on the names or
   username and etc... 
3. If the data is not valid only the wrong input fields are cleaned and we are
   back to step 1.
4. If the data is right the confirmation mail is sent. You can use two smtp
   clients, one to act first and another to act as a fallback when the user
   clicks on "did not receive? Resend email". You can also configure the
   fallback smtp to be used at first for some email hosts.
5. The email and token is cached on redis and has a expiration time (1 hour by
   default). When the user open the url with the confirmation token the user
   will be added to the configured ldap organization (`BASE_DN`).
6. You can limit captcha requests and registrations requests per ip hourly.

## Dependencies

* install openldap on your server like on [this](https://www.digitalocean.com/community/tutorials/how-to-install-and-configure-openldap-an-phpldapadmin-on-ubuntu-16-04) tutorial for example
* An http + php server like apache or nginx
* install the php-ldap module and composer
* redis configured with a password
* imagemagick, ocrad and the php dependencies. For ubuntu:
```
sudo apt install redis php-pear composer ocrad imagemagick php-redis # take care on the php version
sudo pear install mail
sudo pear install Net_SMTP
sudo pear install Auth_SASL
sudo pear install mail_mime
```

## Installation

Clone or download this repository to some path and run: `composer install`
Copy the example config to the right place: `mv config.php.example config.php`
Then create a corresponding server vhost and a location like this(nginx):
```nginx
location /signup {
   alias   /var/www/html/;
   index  index.php;

   location ~ ^/signup/(captcha\.php|index\.php) {
      fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
      fastcgi_pass unix:/run/php/php7.4-fpm.sock;                      
      include fastcgi_params;                                          
   }                                                                    
   location ~ ^/signup/static/.+$ {                  
      root /var/www/html/;                                             
      try_files $uri =404;                                             
   }                                                                               
   location  ~ ^/ {                                   
      rewrite ^ /signup/index.php;                                     
   }
}
```
Add your `BASE_URL` and other configuration options to the config.php and you
might be ready to go!

## Customization

If you want to add custom css, javascript or html you can do it by editing the files on the `static/`
folder but be aware that all of them are loaded for any page of the registration process.

The pages you might face inside the registration process are inside the `html/`
folder. You can customized those templates like to add your site url in the end
of registration, add a navbar to head.htm and so on.

The `blacklists/` Folder contains usernames.php and passwords.php which are
lists of usernames and passwords that won't be allowed.

### config.php

The example config contains many comments and the variables are self
explanatory. 
What you need to set up to get going is your redis password,
LDAP's `USER`, `PORT`, `PASSWORD` and `HOST`. You need to set `SMTP` and you can set
`$FALLBACK_SMTP = $SMTP` if you don't want to use the fallback feature.

On the `BASE_DN` variable, '{}' will be replaced by the username.

Don't forget to set your `BASE_URL` correctly. That is the url the log in page
will be presented on, it will end with `/signup` if you used the example nginx
location.

Also you might want to change the email template (`MAIL_TEMPLATE` variable). It
has both a text and a html parameter.

You can also blacklist email services with the `MAIL_HOST_BLACKLIST`. User
registering with those mail services won't be allowed.

`MAIL_HOST_DIRECT_FALLBACK` variable if set will cause the `FALLBACK_SMTP` to be
used instead of SMTP if the user is trying to register with an email host that
is on that list.

### Translating

The `LANG_CC` variable if set will determine different templates to be used by
default.
It is reccomended to set to a 2 letter country code like `il`, `pt`, `us`, `uk`
and so on, but you can use any string. 

To get started templating and translating get started by setting that variable
to some string like "il" and copy the template folder:
```
cp -r templates templates_il
```

Pay attention to the undescore + `LANG_CC` termination. In that example if
LANG_CC is set to `il` then templates_il will be loaded by default. Otherwise
you can access different languages either by passing the termination as a uri
path or with the lang GET parameter like in `?lang=il`. Let's suppose you
created a templates_pt folder. So you can either access that version of the
site with `mysite.com/signup/pt` or `mysite.com/signup?lang=pt`

One important file to edit under templates is strings.php. This file contains
many strings in a template format that are used in some parts of the
application, like validation errors on the frontend. Pay attention to the
template notation with double braces: `{{num}}`.

You can edit `email.php` to change the mail template messages that are sent for
each language.


### Field Validation

Check validators.php.
