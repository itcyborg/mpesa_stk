<?php

namespace App\Traits;

use App\Bill;
use App\Charge;
use App\Invoice;

trait BillHelper
{
    public function createBill($data,$provider,$service,$product)
    {
        try {
            return Bill::updateOrCreate([
                'reference' => $data['reference']
            ], [
                'amount' => $data['amount'],
                'currency' => $data['currency'],
                'channel' => $data['channel'],
                'description' => $data['description'],
                'outlet' => $data['outlet'],
                'billerCode' => $data['billerCode'],
                'provider' => $provider,
                'service' => $service,
                'product' => $product,
                'product_reference_id' => $data['product_reference_id'],
                'user_id' => $data['user_id'],
                'charge' => $this->applyCharge('charge', $provider, $service, $data['amount'], $data['currency'], $product)
            ]);
        }catch (\Throwable $e){
            dd($e);
        }
    }

    public function settleBill($billId,$amount)
    {
        // check if the bill exists
        $bill=Bill::findOrFail($billId);
        // move the money from the source; applies if it is wallet
        if($bill->provider=='wallet'){

        }
        // mark the bill as settled
        if(($bill->amount+$bill->charge)==$amount){
            $bill->status='SUCCESS';
        }
        // mark the invoice as settled if exists
        if($bill->product=='invoice'){
            $invoice=Invoice::findOrFail($bill->product_reference_id);
            $invoice->status='SETTLED';
            $invoice->save();
        }
        $bill->save();
        return $bill;
    }

    private function applyCharge($name,$provider,$service,$amount,$currency='KES',$product=null)
    {
        $charges=Charge::where('provider',$provider)
            ->where('service',$service)
            ->where('currency',$currency)
            ->first();
        $charge=0;
        if($charges){
            if($charges->type=='fixed'){
                $charge=$charges->charge;
            }else{
                $charge=$amount*$charges->charge/100;
            }
        }
        return $charge;
    }

    private function getCost($name,$provider,$service,$currency='KES',$product=null)
    {
        $charges=Charge::where('provider',$provider)
            ->where('service',$service)
            ->where('currency',$currency)
            ->where('product',$product)
            ->first();
        $charge=0;
        if($charges){
            if($charges->type=='fixed'){
                $charge=$charges->charge;
            }else{
                $charge=$charges->charge;
            }
        }
        return $charge;
    }
}
