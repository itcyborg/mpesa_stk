<?php


    namespace App\MPesa;

    class Config
    {
        public static function getAccount($account=null) : object
        {
            // get account info and credentials
            return (object)[
                'consumer_key'=>'',
                'consumer_secret'=>'',
                'shortcode'=>'',
                'passkey'=>'',
                'nominated'=>'', // phone number used to register paybill
                'command_id'=>'', // for b2c
                'security_credential'=>'',
                'initiator'=>''
            ];
        }
    }
