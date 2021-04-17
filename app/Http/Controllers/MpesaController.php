<?php

namespace App\Http\Controllers;

use App\Traits\PaymentHelpers;
use Illuminate\Http\Request;

class MpesaController extends Controller
{
    use PaymentHelpers;
    public function initStk(Request $request)
    {
        $request->validate([
            'phone'=>'number|required',
            'amount'=>'number|required',
            'reference'=>'required',
            'description'=>'required'
        ]);
        return $this->initiateSTK('254'.(int) $request->phone,$request->amount,$request->reference,$request->description);
    }
}
