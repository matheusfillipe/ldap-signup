<?php
$RUNTIME_ERROR = (object)[
        "not_found" => "<center><h2>This page does not exist!</h2></center>",
        "template_not_found" => "Either you did not create the folder '{{template}}' or strings.php is missing on it. Maybe you have set LANG_CC: '{{langcc}}' wrong on config.php?",
        "user_trying_invalid_get" => "INVALID REQUEST. THERE IS NOTHING HERE FOR YOU",
];

$USERNAME_VALIDATION_ERROR = (object)[
        "registered" => "This username is already in use! Please choose another username<br>",
        "no_whitespaces" => "Username cannot contain whitespaces<br>",
        "smaller_than" => "Username must have less than {{num}} characters<br>",
        "bigger_than" => "Username must be bigger than {{num}} characters<br>",
        "no_special_chars" => "The username cannot contain special characters<br>",
        "no_number_begining" => "The username cannot begin with a number<br>",
        "blacklisted" => "That Username is not allowed!<br>",
];

$FIRST_NAME_VALIDATION_ERROR = (object)[
        "no_whitespaces" => "First name cannot contain whitespaces<br>",
        "smaller_than" => "First name must have less than {{num}} characters<br>",
        "bigger_than" => "First name must be bigger than {{num}} characters<br>",
        "no_special_chars" => "The first name cannot contain special characters or numbers<br>",
];

$LAST_NAME_VALIDATION_ERROR = (object)[
        "no_whitespaces" => "Last name cannot contain whitespaces<br>",
        "smaller_than" => "Last name must have less than {{num}} characters<br>",
        "bigger_than" => "Last name must be bigger than {{num}} characters<br>",
        "no_special_chars" => "The last name cannot contain special characters or numbers<br>",
];

$EMAIL_VALIDATION_ERROR = (object)[
        "registered" => "This email is already belongs to an account. Did you <a href='{{link}}'>forget your password?</a><br>",
        "invalid" => "Invalid email format<br>",
        "blacklisted" => "This email service is not allowed<br>",
        "pending" => "This email is already pending approval, check your mailbox or try to register with a different email<br>",

];

$PASSWORD_VALIDATION_ERROR = (object)[
        "no_match" => "Passwords do not match!<br>",
        "bigger_than" => "Password should have at least {{num}} characters<br>",
        "smaller_than" => "Password is too big. Should have at max {{num}} characters<br>",
        "blacklisted" => "That password is not allowed!<br>",
        "shared_inclusion" => "Your password cannot contain any of your names or email neither the names can contain the password<br>"
];

$STRINGS = (object)[
        "email_confirmation" => "<h1>Email Confirmation</h1>",
        "reloading_captcha" => "Loading...",
];

?>
