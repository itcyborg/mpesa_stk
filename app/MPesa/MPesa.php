<?php


    namespace App\MPesa;


    use App\Http\Controllers\GuzzleClient;
    use Carbon\Carbon;
    use Exception;
    use Illuminate\Support\Facades\Cache;
    use Illuminate\Support\Facades\Http;
    use Throwable;
    use function openssl_public_encrypt;

    /**
     * Class MPesa
     *
     * @package App\MPesa
     * @author  isaac
     */
    class MPesa
    {
        /**
         * @param        $phone
         * @param        $amount
         * @param        $reference
         * @param        $description
         * @param string $account
         *
         * @return mixed
         * @throws Exception
         * @author isaac
         */
        public static function STK($phone,$amount,$reference,$description,$account='default')
        {
            // using cache
            $token=Cache::remember('auth'.$account,3500,function() use ($account){
                return json_decode(self::authenticate($account))->access_token;
            });
            // without cache
//            $token=json_decode(self::authenticate($account))->access_token;
            $account=Config::getAccount($account);
            $url = trim(config('mpesa.endpoints.base'),'/').config('mpesa.endpoints.stk');
            $headers=[
                "Content-Type"=>'application/json',
                'Authorization'=>'Bearer '.$token
            ];
            $timestamp=Carbon::now()->format('Ymdhis');
            $password=self::generatePassword($account->shortcode,$account->passkey,$timestamp);
            $callback=config('mpesa.endpoints.stk_callback');

            $curl_post_data = array(
                //Fill in the request parameters with valid values
                'BusinessShortCode' =>$account->shortcode,
                'Password' => $password,
                'Timestamp' => $timestamp,
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => number_format($amount),
                'PartyA' => $phone,
                'PartyB' => $account->shortcode,
                'PhoneNumber' => $phone,
                'CallBackURL' =>$callback,
                'AccountReference' => $reference,
                'TransactionDesc' => $description
            );

            $response=json_decode(GuzzleClient::postJson($url,$headers,$curl_post_data));
            return $response;
        }

        /**
         * @param $account
         *
         * @return string
         * @throws Exception
         * @author isaac
         */
        private static function authenticate($account)
        {
            try {
                $endpoint=config('mpesa.endpoints.base').config('mpesa.endpoints.auth');
                $credentials=Config::getAccount($account);
                $secret=base64_encode($credentials->consumer_key . ':' . $credentials->consumer_secret);
                $headers=[
                    'Authorization'=> 'Basic '.$secret
                ];
                $response=GuzzleClient::request('GET',$endpoint,$headers);
                return $response;
            }catch (Throwable $e){
                report($e);
                abort(500);
            }
        }

        /**
         * @param $shortcode
         * @param $passkey
         * @param $timestamp
         *
         * @return string
         * @author isaac
         */
        private static function generatePassword($shortcode,$passkey,$timestamp){
            return base64_encode($shortcode.$passkey.$timestamp);
        }

        /**
         * @param        $checkoutRequestId
         * @param string $account
         *
         * @return mixed
         * @throws Exception
         * @author isaac
         */
        public static function validateSTK($checkoutRequestId,$account='default')
        {
            $token=json_decode(self::authenticate($account))->access_token;
            $account=Config::getAccount($account);
            $url = trim(config('mpesa.endpoints.base'),'/').config('mpesa.endpoints.query_stk');
            $headers=[
                "Content-Type"=>'application/json',
                'Authorization'=>'Bearer '.$token
            ];
            $timestamp=Carbon::now()->format('Ymdhis');
            $password=self::generatePassword($account->shortcode,$account->passkey,$timestamp);
            $callback=config('mpesa.endpoints.stk_callback');

            $curl_post_data = array(
                //Fill in the request parameters with valid values
                'Password' => $password,
                'Timestamp' => $timestamp,
                'CheckoutRequestID'=>$checkoutRequestId,
                'BusinessShortCode'=>$account->shortcode
            );


            $response=json_decode(GuzzleClient::postJson($url,$headers,$curl_post_data));
            return $response;
        }

        /**
         * @param string $account
         *
         * @return mixed
         * @throws Exception
         * @author isaac
         */
        public static function register($account='default')
        {
            $token=json_decode(self::authenticate($account))->access_token;
            $account=Config::getAccount($account);
            $url = trim(config('mpesa.endpoints.base'),'/').'/mpesa/c2b/v1/registerurl';
            $headers=[
                "Content-Type"=>'application/json',
                'Authorization'=>'Bearer '.$token
            ];

            $curl_post_data = array(
                //Fill in the request parameters with valid values
                "ConfirmationURL"=> env('SAFARICOM_STK_CALLBACK'),
	            "ValidationURL"=> env('SAFARICOM_STK_CALLBACK').'/validate',
                'ResponseType'=>'Completed',
                'ShortCode'=>$account->shortcode
            );


            $response=json_decode(GuzzleClient::postJson($url,$headers,$curl_post_data));
            return $response;
        }

        /**
         * @param      $account
         * @param null $startDate
         * @param null $endDate
         *
         * @author isaac
         */
        public function pullTransactions($account, $startDate=null, $endDate=null)
        {
            // TODO Verify and validate with production urls/ paybill
            try{

                if($startDate==null){
                    $startDate=Carbon::now()->startOfDay();
                }
                if($endDate==null){
                    $startDate=Carbon::now()->endOfDay();
                }
                $token = json_decode(self::authenticate($account))->access_token;

                $account=Config::getAccount($account);
                $url = trim(env('SAFARICOM_BASE_ENDPOINT','https://api.safaricom.co.ke'),'/').'/pulltransactions/v1/query';
                $headers=[
                    "Content-Type"=>'application/json',
                    'Authorization'=>'Bearer '.$token
                ];
                $payload=[
                    'ShortCode'=>$account->shortcode,
                    'StartDate'=>$startDate,
                    'EndDate'=>$endDate,
                    'OffSetValue'=>'0'
                ];
                $response=Http::withoutVerifying()->withHeaders($headers)->post($url,$payload);
            }catch (Throwable $e){
                report($e);
                abort(500);
            }
        }

        /**
         * @param $account
         *
         * @author isaac
         */
        public function registerPullRequest($account)
        {
            // TODO Verify and validate with production urls/ paybill
            try{
                $token = json_decode(self::authenticate($account))->access_token;

                $account=Config::getAccount($account);
                $url = trim(env('SAFARICOM_BASE_ENDPOINT','https://api.safaricom.co.ke'),'/').'/pulltransactions/v1/register';
                $headers=[
                    "Content-Type"=>'application/json',
                    'Authorization'=>'Bearer '.$token
                ];
                $payload=[
                    'ShortCode'=>$account->shortcode,
                    'RequestType'=>'Pull',
                    'NominatedNumber'=>$account->nominated,
                    'CallBackURL'=>env('APP_URL').'/api/payments/pulltransactions'
                ];
                $response=Http::withoutVerifying()->withHeaders($headers)->post($url,$payload);
            }catch (Throwable $e){

            }
        }
    }
