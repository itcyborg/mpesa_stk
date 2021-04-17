<?php

return [
    'endpoints'=>[
        'base'=>env('SAFARICOM_BASE_ENDPOINT','https://api.safaricom.co.ke'),
        'auth'=>'/oauth/v1/generate?grant_type=client_credentials',
        'stk'=>'/mpesa/stkpush/v1/processrequest',
        'query_stk'=>'/mpesa/stkpushquery/v1/query',
        'stk_callback'=>config('app.url').'/receiver/stk',
        'b2c_queue_timeout'=>config('app.url').'/receiver/b2c_timeout',
        'b2c_result'=>config('app.url').'/receiver/b2c_result',
        'b2c_endpoint'=>env('SAFARICOM_B2C_ENDPOINT','/mpesa/b2c/v1/paymentrequest')
    ]
];
