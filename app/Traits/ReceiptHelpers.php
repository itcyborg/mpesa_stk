<?php

namespace App\Traits;

use App\Notifications\SendReceipt;
use App\Receipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

trait ReceiptHelpers
{
    public function makeReceipt($customer,$items,$customer_type,$merchant_id)
    {
        $data=[
            'customer'=>$customer,
            'items'=>$items,
            'customer_type'=>$customer_type,
            'merchant_id'=>$merchant_id
        ];
        if($id=$this->logReceipt($data)){
            $this->sendReceipt($id);
        }
    }

    public function sendReceipt($id)
    {
        $receipt=Receipt::find($id)->first();
        if($receipt){
            if($receipt->customer_type=='phone'){
                // use sms
            }
            if($receipt->customer_type=='email'){
                // use email
                Mail::to($receipt->customer)->send(new \App\Mail\SendReceipt($receipt));
            }
        }
    }

    public function logReceipt($data)
    {
        return Receipt::create($data);
    }
}
