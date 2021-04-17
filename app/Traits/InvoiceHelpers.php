<?php

namespace App\Traits;

use App\Customers;
use App\Invoice;
use App\InvoiceItems;
use App\Merchant;

/**
 * Trait InvoiceHelpers
 *
 * @package App\Traits
 * @author  isaac
 */
trait InvoiceHelpers
{
    use ReceiptHelpers;

    /**
     * @param $invoiceId
     * @param $amount
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @author isaac
     */
    public function payInvoice($invoiceId,$amount)
    {
        $invoice=Invoice::find($invoiceId);
        if($invoice){
            // exact amount
            if($invoice->total_amount==$amount){
                $invoice->status='SETTLED';
                if($invoice->save()){
                    $customer=Customers::find($invoice->customer_id)->email;
                    $merchant_id=Merchant::find($invoice->merchant_id);
                    $items=$invoice->items()->get();
                    $this->makeReceipt($customer,$items,'email',$merchant_id);
                }
            }

            // underpayment
            if($invoice->total_amount<$amount){

            }

            //overpayment
            if($invoice->total_amount>$amount){

            }
        }
        return response('invoice not found',404);
    }
}
