<?php

namespace App\Traits;

use Stripe\PaymentIntent;
use Stripe\Stripe;

trait StripeHelper
{
    private function authenticate()
    {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));
    }

    public function paymentIntent($amount,$currency,$metadata)
    {
        $this->authenticate();
        $metadata=array_merge($metadata,['integration_check' => 'accept_a_payment']);
//        dd($metadata);
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            // Verify your integration in this guide by including this parameter
            'metadata' => $metadata
        ]);
        return json_decode(json_encode(['client_secret'=>$intent->client_secret,'client_publishable'=>'pk_test_51HVDIFCH25OySMVFrIBEQK1wQKOF39nx8bv3poyKlc6GNY0mwREw0QpwHOX06ZZEB3xjbRMbTwWXA2krAgqZNimw00C4yiwqXA']));
    }
}
