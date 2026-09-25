<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | Supported: "http", "log"
    |
    */

    'driver' => env('MSG91_DRIVER', 'http'),

    'auth_key' => env('MSG91_AUTH_KEY'),

    'country_code' => env('MSG91_COUNTRY_CODE', '91'),

    'sender_id' => env('MSG91_SENDER_ID'),

    'whatsapp' => [
        'integrated_number' => env('MSG91_WHATSAPP_INTEGRATED_NUMBER'),
        'namespace' => env('MSG91_WHATSAPP_NAMESPACE'),
        'language' => env('MSG91_WHATSAPP_LANGUAGE', 'en'),
    ],

    'otp' => [
        'template_id' => env('MSG91_OTP_TEMPLATE_ID'),
        'expiry' => (int) env('MSG91_OTP_EXPIRY', 5),
        'length' => (int) env('MSG91_OTP_LENGTH', 6),
    ],

    'http' => [
        'timeout' => (int) env('MSG91_HTTP_TIMEOUT', 10),
        'retry' => (int) env('MSG91_HTTP_RETRY', 1),
    ],

    'endpoints' => [
        'sms' => env('MSG91_SMS_ENDPOINT', 'https://control.msg91.com/api/v5/flow/'),
        'whatsapp' => env('MSG91_WHATSAPP_ENDPOINT', 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/bulk/'),
        'whatsapp_session' => env('MSG91_WHATSAPP_SESSION_ENDPOINT', 'https://control.msg91.com/api/v5/whatsapp/whatsapp-outbound-message/'),
        'whatsapp_balance' => env('MSG91_WHATSAPP_BALANCE_ENDPOINT', 'https://control.msg91.com/api/v5/subscriptions/fetchPrepaidBalance'),
        'otp' => env('MSG91_OTP_ENDPOINT', 'https://control.msg91.com/api/v5/otp'),
        'otp_verify' => env('MSG91_OTP_VERIFY_ENDPOINT', 'https://control.msg91.com/api/v5/otp/verify'),
        'otp_retry' => env('MSG91_OTP_RETRY_ENDPOINT', 'https://control.msg91.com/api/v5/otp/retry'),
    ],

];
