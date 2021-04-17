<?php

namespace App\Traits;

use App\Http\Controllers\API\ApiTokenController;
use App\Invoice;
use App\User;
use App\Wallet;

trait WalletHelpers
{
    protected $source;
    public function rechargeWallet(int $wallet_id,float $amount,$options=null): bool
    {
        $wallet=Wallet::find($wallet_id);
        $user=User::find($wallet->holder_id);
        $holder_wallet=$user->getWallet($wallet->slug);
        if($holder_wallet->deposit($amount)){
            return true;
        }else{
            return false;
        }
    }

    public function payMerchant(Invoice $invoice,$amount):bool
    {
        dd($invoice);
    }

    public function deduct($walletId,$amount)
    {
        try {
            $wallet_slug = Wallet::find($walletId)->slug;
            $user = User::find(ApiTokenController::getUserId());
            $wallet = $user->getWallet($wallet_slug);
            !$wallet->withdraw($amount);
            $adminUser = User::where('email', env('SUPER_ADMIN'))->first();
            $adminUser->deposit($amount);
            return true;
        }catch (\Throwable $e){
            throw new \Exception('An error occurred.',500);
        }
    }


}
