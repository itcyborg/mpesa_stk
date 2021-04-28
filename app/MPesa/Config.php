<?php


    namespace App\MPesa;

    use App\Models\Account;
    use Illuminate\Http\Request;

    class Config
    {
        public static function getAccount($account='default') : object
        {

            $account=Account::where('accountName',$account)->first();
            return (object) $account->details;
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

        public function saveAccount(Request $request)
        {
            $request->validate(
                [
                    'consumer_key'=>'required',
                    'consumer_secret'=>'required',
                    'shortcode'=>'required',
                    'passkey'=>'required'
                ]
            );

            return Account::first()->details;

            return Account::create([
                'accountName'=>'default1',
                'details'=>(object) $request->all()
            ]);

        }
    }
