<?php

namespace App\Traits;

use App\Http\Controllers\GuzzleClient;
use App\Models\MpesaLogs;
use App\Models\Payments;
use App\Models\STKRequests;
use App\MPesa\MPesa;

trait PaymentHelpers
{
    use WalletHelpers,InvoiceHelpers,BillHelper;
    public function logPayment($data)
    {
        Payments::create($data);
    }

    public function logTransactions($data,$type=null)
    {
        MpesaLogs::create(['content'=>$data,'type'=>$type]);
    }

    public function initiateSTK($phone,int $amount,$reference,$description,$channel='WEB',$user_id,$callback=null,$type=null,$account='default')
    {
        try {
            $data=json_encode(MPesa::STK($phone,$amount,$reference,$description,$account));
            $this->logSTKRequest($data,$type,$reference,$description,$phone,$amount,$user_id,$channel,$callback);
            return $data;
        }catch (\Throwable $e){
            return $e->getMessage();
        }
    }

    public function logSTKRequest($data,$type,$reference,$description,$phone,$amount,$user_id,$channel='WEB',$callback)
    {
        $data=json_decode($data);
        try {
            STKRequests::create([
                'type'=>$type,
                'reference'=>$reference,
                'description'=>$description,
                'CheckoutRequestID'=>$data->CheckoutRequestID,
                'CustomerMessage'=>$data->CustomerMessage,
                'MerchantRequestID'=>$data->MerchantRequestID,
                'ResponseCode'=>$data->ResponseCode,
                'ResponseDescription'=>$data->ResponseDescription,
                'PhoneNumber'=>$phone,
                'Amount'=>$amount,
                'user_id'=>$user_id,
                'channel'=>$channel,
                'callback_uri'=>$callback
            ]);
        }catch (\Throwable $e){
            return $e->getMessage();
        }
    }

    public function validateTransaction()
    {
    }

    public function confirmTransaction($data)
    {
        if(fnmatch('BILL-*',$data['acc_no']) || fnmatch('BILL',$data['acc_no'])){
            $acc_no=strtoupper($data['acc_no']);
            $bill_id=explode('-',$acc_no)[1];
            $this->settleBill($bill_id,$data['amount']);
        }
    }


    public function confirmSTKTransaction($checkoutRequestId)
    {
        // look for the transaction request
        $transaction=STKRequests::where('CheckoutRequestID',$checkoutRequestId)->first();
        if($transaction){
            // mark the transaction as complete
        }
    }

    public function triggerCallback($endpoint,$data)
    {
        GuzzleClient::postJson($endpoint,['Content-Type' => 'application/json'],$data);
    }
}
