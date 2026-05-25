<?php

return [
    'wechat' => [
        'app_id' => env('WECHAT_PAY_APP_ID', ''),
        'mch_id' => env('WECHAT_PAY_MCH_ID', ''),
        'api_key' => env('WECHAT_PAY_API_KEY', ''),
        'cert_path' => env('WECHAT_PAY_CERT_PATH', ''),
        'key_path' => env('WECHAT_PAY_KEY_PATH', ''),
        'notify_url' => env('WECHAT_PAY_NOTIFY_URL', ''),
    ],

    'alipay' => [
        'app_id' => env('ALIPAY_APP_ID', ''),
        'private_key' => env('ALIPAY_PRIVATE_KEY', ''),
        'public_key' => env('ALIPAY_PUBLIC_KEY', ''),
        'notify_url' => env('ALIPAY_NOTIFY_URL', ''),
        'return_url' => env('ALIPAY_RETURN_URL', ''),
        'sandbox' => env('ALIPAY_SANDBOX', false),
    ],
];
