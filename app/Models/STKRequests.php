<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class STKRequests extends Model
{
    protected $table='stkrequests';
    
    protected $fillable = [
        'type',
        'reference',
        'description',
        'CheckoutRequestID',
        'CustomerMessage',
        'MerchantRequestID',
        'ResponseCode',
        'ResponseDescription',
        'PhoneNumber',
        'Amount',
        'user_id',
        'channel',
        'callback_uri'
    ];


    protected $casts=[
        'created_at'=>'date:d M Y',
        'updated_at'=>'date:d M Y'
    ];
}
