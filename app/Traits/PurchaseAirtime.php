<?php

namespace App\Traits;

use AfricasTalking\SDK\AfricasTalking;
use App\AirtimePurchase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

trait PurchaseAirtime
{
    private function initateSession(): \AfricasTalking\SDK\Airtime
    {
        $username = env('AFRICA_TALKING_USERNAME'); // use 'sandbox' for development in the test environment
        $apiKey   = env('AFRICA_TALKING_KEY'); // use your sandbox app API key for development in the test environment
        $africasTalking= new AfricasTalking($username, $apiKey);
        return $africasTalking->airtime();
    }
    protected function buyAirtime(string $phone,int $amount,$channel='WEB'): ?bool
    {
        $airtime=$this->initateSession();
        $parameters[]=[
            'currencyCode'=>'KES',
            'amount'=>$amount,
            'phoneNumber'=>$phone
        ];
        $response=$airtime->send(['recipients'=>$parameters]);
        return $this->logAirtimePurchase($response,$channel);
    }

    protected function buyAirtimeMultiple(array $phones,int $amount,$channel='WEB'): ?bool
    {
        $airtime=$this->initateSession();
        $recipients=[];
        foreach ($phones as $phone){
            $recipients[]=[
                'currencyCode'=>'KES',
                'amount'=>$amount,
                'phoneNumber'=>$phone
            ];
        }
        $response=$airtime->send(['recipients'=>$recipients]);
        return $this->logAirtimePurchase($response,$channel);
    }

    protected function logAirtimePurchase($data,$channel): ?bool
    {
        $response=$data;
        if($response['status']=='success'){
            $responseData=$response['data'];
            $airtimeResponses=$responseData->responses;
            foreach ($airtimeResponses as $airtimeResponse){
                $phoneNumber=$airtimeResponse->phoneNumber;
                $message=$airtimeResponse->errorMessage;
                $airtimeAmount=$airtimeResponse->amount;
                $status=$airtimeResponse->status;
                $requestId=$airtimeResponse->requestId;
                $discount=$airtimeResponse->discount;
                AirtimePurchase::create([
                    'phone_number'=>$phoneNumber,
                    'amount'=>$airtimeAmount,
                    'channel'=>$channel,
                    'status'=>strtolower($status),
                    'message'=>$message,
                    'request_id'=>$requestId,
                    'discount'=>$discount,
                    'user_id'=>Auth::id()
                ]);
            }
        }
        if($response['status']=='success'){
            return true;
        }else{
            return false;
        }
    }

}
