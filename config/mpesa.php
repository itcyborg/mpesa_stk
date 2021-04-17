<?php

return [
    'endpoints'=>[
        'base'=>env('SAFARICOM_BASE_ENDPOINT','https://api.safaricom.co.ke'),
        'auth'=>'/oauth/v1/generate?grant_type=client_credentials',
        'stk'=>'/mpesa/stkpush/v1/processrequest',
        'query_stk'=>'/mpesa/stkpushquery/v1/query',
        'stk_callback'=>config('app.url').'/receiver/stk'
    ]
];
