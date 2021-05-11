<?php
require_once "Mail.php";
include('Mail/mime.php');
include 'config.php';

function send_mail(string $email, object $smtp, object $message) {
    $crlf = "\r\n";

    $headers = array(
        'From' => $smtp->from,
        'To' => $email,
        'Subject' => $message->subject
    );

    $smtp = Mail::factory('smtp', array(
            'host' => $smtp->host,
            'port' => $smtp->port,
            'auth' => true,
            'username' => $smtp->username, //your gmail account
            'password' => $smtp->password // your password
        ));

    // Creating the Mime message
    $mime = new Mail_mime($crlf);

    // Setting the body of the email
    $mime->setTXTBody($message->text);
    $mime->setHTMLBody($message->html);

    $body = $mime->get();
    $headers = $mime->headers($headers);

    // Send the mail
    $mail = $smtp->send($email, $headers, $body);

    //check mail sent or not
    if (PEAR::isError($mail)) {
        return false;
    } else {
        return true;
    }
}

/* send_mail("mattf@tilde.club", $SMTP, (object) [ */
/*     "subject" => "Please confirm your email", */ 
/*     "text" => "Plain tet", */
/*     "html" => "<html><body><p>HTML message</p><h2>This is not mere text</h2></body></html>" */
/* ]) */
?>
