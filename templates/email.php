<?php
$MAIL_TEMPLATE = (object)[
        "subject" => "Confirm your email",
        "text"    => "To complete your registration please paste this to your browser: {{url}}",
        "html"    => "<html><body>
                <h2>Almost there! Click on the link bellow to confirm your email address</h2>
                <a href='{{url}}'>Confirm</a>
        </body></html>"
];


$RECOVERY_EMAIL_TEMPLATE = (object)[
        "subject" => "Change your password!",
        "text"    => "Seems you requested a password change. If that wasn't you please ignore this message. Otherwise go to this url to change your password: {{url}}",
        "html"    => "<html><body>
                <h3>Seems you requested a password change. If that wasn't you please ignore this message. Otherwise go to this url to change your password</h3>
                <a href='{{url}}'>Click here</a> to change your password
        </body></html>"
];


// Add the support email there
$PASSWORD_CHANGED_EMAIL_TEMPLATE = (object)[
        "subject" => "Your password was changed",
        "text"    => "Your password was chanegd successfully. If this wasn't you please contact support",
        "html"    => "<html><body>
                <h3>Your password was chanegd successfully. If this wasn't you please contact support</h3>
        </body></html>"
];
