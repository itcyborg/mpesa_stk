<?php

namespace App\Traits;

use App\B2CRequests;
use App\B2CResponse;
use App\Callback;
use App\Ecwid\Ecwid;
use App\Http\Controllers\API\ApiTokenController;
use App\Http\Controllers\GuzzleClient;
use App\Invoice;
use App\Jobs\SendStkCallback;
use App\MPesa\MPesa;
use App\MpesaLogs;
use App\PaybillTill;
use App\Payments;
use App\Revenue;
use App\STKRequests;
use Illuminate\Support\Facades\Request;

trait PaymentHelpers
{
    use WalletHelpers,InvoiceHelpers,BillHelper;
    public function logPayment($data)
    {
        $payload=$data;
        $data['user_id']=$this->getMerchantPaybillsUser($data['business_number']);
        if($data['user_id']!==0){
            Callback::send($data['user_id'],$payload,'payment');
        }
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
            if($type==null){
                $type='wallet';
            }
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
        if(fnmatch('INV-*',$data['acc_no']) || fnmatch('inv-*',$data['acc_no'])){
            $acc_no=strtoupper($data['acc_no']);
            $invoice_id=explode('-',$acc_no)[1];
            $this->payInvoice($invoice_id,$data['amount']);
        }
        if(fnmatch('WLT-*',$data['acc_no']) || fnmatch('wlt-*',$data['acc_no'])){
            $acc_no=strtoupper($data['acc_no']);
            $wallet_id=explode('-',$acc_no)[1];
            $this->rechargeWallet($wallet_id,$data['amount']);
        }
        if(fnmatch('BILL-*',$data['acc_no']) || fnmatch('BILL',$data['acc_no'])){
            $acc_no=strtoupper($data['acc_no']);
            $bill_id=explode('-',$acc_no)[1];
            $this->settleBill($bill_id,$data['amount']);
        }
        if(fnmatch('EX*',$data['acc_no']) || fnmatch('ex*',$data['acc_no'])){
            $acc_no=strtoupper($data['acc_no']);
            $ecwid=new Ecwid($acc_no,$data['amount']);
            $ecwid->confirmTransaction();
        }
    }

    public function withdrawToMpesa($phone,$amount,$remarks,$account='B2C_DEFAULT_ACCOUNT',array $callbacks=null)
    {
        $response= Mpesa::B2C($phone,$amount,$account,$remarks,$callbacks,ApiTokenController::getUserId(Request::instance()));
        return $response;
    }

    public function addRevenue($source,$channel,$amount)
    {
        try {
            return Revenue::create([
                'source' => $source,
                'channel' => $channel,
                'amount' => $amount
            ]);
        }catch (\Throwable $e){
            dd($e->getMessage());
        }
    }

    public function b2cReceiverLog($response)
    {
        $response=json_decode(json_encode($response),false);
        if($response->Result->ResultCode==2001){
            $conversationID=$response->Result->ConversationID;
            $b2cRequest=B2CRequests::where('ConversationID',$conversationID)->first();
            $b2cRequest->status='Failed';
            $b2cRequest->save();
            return 'The initiator information is invalid.';
        }
        if($response->Result->ResultCode==2040){
            $conversationID=$response->Result->ConversationID;
            $b2cRequest=B2CRequests::where('ConversationID',$conversationID)->first();
            $b2cRequest->status='Failed';
            $b2cRequest->save();
            return "Credit Party customer type (Unregistered or Registered Customer) can't be supported by the service.";
        }

        if($response->Result->ResultCode==0){
            $params=$response->Result->ResultParameters->ResultParameter;
            $transactionAmount=null;
            $transactionReceipt=null;
            $b2CRecipientIsRegisteredCustomer=null;
            $receiverPartyPublicName=null;
            $transactionCompletionDateTime=null;
            $b2CUtilityAccountAvailableFunds=null;
            $b2CWorkingAccountAvailableFunds=null;
            $b2CChargesPaidAccountAvailableFunds=null;
            $conversationID=$response->Result->ConversationID;
            $originatorConversationID=$response->Result->OriginatorConversationID;
            $resultDesc=$response->Result->ResultDesc;
            $resultCode=$response->Result->ResultCode;
            $resultType=$response->Result->ResultType;
            $name=null;
            $phone=null;
            foreach ($params as $param) {
                if($param->Key=='TransactionAmount'){
                    $transactionAmount=$param->Value;
                }
                if($param->Key=='TransactionReceipt'){
                    $transactionReceipt=$param->Value;
                }
                if($param->Key=='B2CRecipientIsRegisteredCustomer'){
                    $b2CRecipientIsRegisteredCustomer=$param->Value;
                }
                if($param->Key=='B2CChargesPaidAccountAvailableFunds'){
                    $b2CChargesPaidAccountAvailableFunds=$param->Value;
                }
                if($param->Key=='ReceiverPartyPublicName'){
                    $receiverPartyPublicName=$param->Value;
                    $tmp=explode('-',$receiverPartyPublicName);
                    $phone=trim($tmp[0]);
                    $name=trim($tmp[1]);
                }
                if($param->Key=='TransactionCompletedDateTime'){
                    $transactionCompletionDateTime=$param->Value;
                }
                if($param->Key=='B2CUtilityAccountAvailableFunds'){
                    $b2CUtilityAccountAvailableFunds=$param->Value;
                }
                if($param->Key=='B2CWorkingAccountAvailableFunds'){
                    $b2CWorkingAccountAvailableFunds=$param->Value;
                }
            }
            $b2cRequest=B2CRequests::where('ConversationID',$conversationID)->first();
            $b2cRequest->status='Success';
            $b2cRequest->save();
            $b2cresponse=B2CResponse::create([
                'ResultType'=>$resultCode,
                'ResultCode'=>$resultCode,
                'ResultDesc'=>$resultDesc,
                'OriginatorConversationID'=>$originatorConversationID,
                'ConversationID'=>$conversationID,
                'TransactionID'=>$transactionReceipt,
                'TransactionAmount'=>$transactionAmount,
                'TransactionReceipt'=>$transactionReceipt,
                'B2CRecipientIsRegisteredCustomer'=>$b2CRecipientIsRegisteredCustomer,
                'B2CChargesPaidAccountAvailableFunds'=>$b2CChargesPaidAccountAvailableFunds,
                'ReceiverPartyPublicName'=>$receiverPartyPublicName,
                'phone'=>$phone,
                'fullName'=>$name,
                'TransactionCompletedDateTime'=>$transactionCompletionDateTime,
                'B2CUtilityAccountAvailableFunds'=>$b2CUtilityAccountAvailableFunds,
                'B2CWorkingAccountAvailableFunds'=>$b2CWorkingAccountAvailableFunds
            ]);

            if($b2cresponse){
            }
        }
    }

    public function confirmSTKTransaction($checkoutRequestId)
    {
        // look for the transaction request
        $transaction=STKRequests::where('CheckoutRequestID',$checkoutRequestId)->first();
        if($transaction){
            if($transaction->type==='wallet'){
                $walletID=explode('_',$transaction->reference)[1];
//                $this->rechargeWallet($walletID,$transaction->Amount);
            }
            if($transaction->type=='api'){
                $transaction->status='success';
                $transaction->save();

                $data=collect($transaction);
                //trigger callback
                $this->triggerCallback($transaction->callback_uri,$data->forget(['id','user_id']));
                return response('Accept',200);
            }
            if($transaction->type=='invoice'){
                $invoice=Invoice::find($transaction->reference);
                $invoice->status='settled';
                $invoice->save();
                return response('Accept',200);
            }
            if($transaction->type=='ecwid'){
                if(fnmatch('EX*',$transaction->reference) || fnmatch('ex*',$transaction->reference)){
                    $acc_no=strtoupper($transaction->reference);
                    $ecwid=new Ecwid($acc_no);
                    $ecwid->confirmTransaction();
                }
                return response('Accept',200);
            }
        }
    }

    public function triggerCallback($endpoint,$data)
    {
        GuzzleClient::postJson($endpoint,['Content-Type' => 'application/json'],$data);
    }

    public function getMerchantPaybillsUser($paybill)
    {
        $paybill=PaybillTill::where('business_number',$paybill)->first();
        if($paybill){
            return $paybill->merchant_id;
        }
        return 0;
    }

    public function initiateSTKCallback($checkout_request_id)
    {
        $request=json_decode(json_encode($checkout_request_id));
        $checkout_request_id=$request->Body->stkCallback->CheckoutRequestID;
        $stkRequest=STKRequests::where('CheckoutRequestID',$checkout_request_id)->first();
        if($stkRequest){
            // get callback
            $callback= $stkRequest->callback_uri;
            ($callback)? SendStkCallback::dispatch($callback,(array) $request)->afterResponse():response('Ok',200);
            return response('Ok',200);
        }
        return response('Ok',200);
    }
}
