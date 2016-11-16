# PostmanCanFail
A simple plugin that reads Postman SMTP Mailer logs and send a notice via `mail()` or **Rollbar** in case of error. You can provide a default config file as follow:
```php
<?php
// pcf/default.php
return [
    'fail_type'     => PostmanCanFail::LOG_VIA_BOTH, // or LOG_VIA_MAIL or LOG_VIA_ROLLBAR
    'email'         => 'example@example.it',
    'rollbar_token' => 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxx'
];
```