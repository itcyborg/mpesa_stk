<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MpesaLogs extends Model
{
    protected $fillable=['content','type'];

    protected $casts=[
        'content'=>'json',
        'created_at'=>'date:d M Y',
        'updated_at'=>'date:d M Y'
    ];
}
