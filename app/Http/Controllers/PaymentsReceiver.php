<?php

namespace App\Http\Controllers;

use App\Traits\PaymentHelpers;
use Illuminate\Http\Request;

class PaymentsReceiver extends Controller
{
    use PaymentHelpers;

//    public function validate(Request $request)
//    {
//        // validate transactions for orders
//
//        $this->logTransactions($request->all(),'validate_c2b');
//        if(isset($request->all()['TransactionType'])=="Pay Bill"){
//            $this->validatePaybill($request->all());
//        }
//
//    }

    public function receive(Request $request)
    {
        $this->logTransactions($request->all(),'c2b');
        // check if is stk callback
        if(isset($request->all()['Body']['stkCallback'])){
            $this->stk($request->all());
            return $this->initiateSTKCallback($request->all());
        }

        // paybill/ till
        if(isset($request->all()['TransactionType'])=="Pay Bill"){
            $this->paybill($request->all());
        }
    }

    private function paybill($data){
        $data=[
            'phone_no'=>$data['MSISDN'],
            'sender_first_name'=>$data['FirstName'],
            'sender_middle_name'=>$data['MiddleName'],
            'sender_last_name'=>$data['LastName'],
            'transaction_id'=>$data['TransID'],
            'amount'=>$data['TransAmount'],
            'business_number'=>$data['BusinessShortCode'],
            'acc_no'=>$data['BillRefNumber'],
            'transaction_type'=>$data['TransactionType'],
            'transaction_time'=>$data['TransTime']
        ];
        //log payment
        $this->logPayment($data);
        return $this->confirmTransaction($data);
    }

    private function stk($data)
    {
        $this->receiveSTK($data);
    }

    private function receiveSTK($data)
    {
        //convert the response to an object
        $data=json_decode(json_encode($data));

        //check if the transaction was successful
        if($data->Body->stkCallback->ResultCode==0){
            $checkoutRequestID=$data->Body->stkCallback->CheckoutRequestID;
            $items=$data->Body->stkCallback->CallbackMetadata->Item;
            $items=json_decode(json_encode($items));
            foreach ($items as $item) {
                if ($item->Name === 'Amount') {
                    $amount = $item->Value;
                }
                if ($item->Name === 'MpesaReceiptNumber') {
                    $mpesaReceiptNumber = $item->Value;
                }
                if ($item->Name === 'TransactionDate') {
                    $transactionDate = $item->Value;
                }
                if ($item->Name === 'PhoneNumber') {
                    $phoneNumber = $item->Value;
                }
            }

            $data=[
                'phone_no'=>$phoneNumber,
                'sender_first_name'=>'',
                'sender_middle_name'=>'',
                'sender_last_name'=>'',
                'transaction_id'=>$mpesaReceiptNumber,
                'amount'=>$amount,
                'business_number'=>'',
                'acc_no'=>'',
                'transaction_type'=>'STK C2B',
                'transaction_time'=>$transactionDate
            ];
            $this->logPayment($data);
            $this->confirmSTKTransaction($checkoutRequestID);
        }
    }

    public function pullReceiver(Request $request)
    {
        $this->logTransactions($request->all(),'PULL');
    }
}
