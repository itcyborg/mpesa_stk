<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Account extends Model
{
    use HasFactory;

    protected $fillable=[
        'accountName',
        'details',
//        'uuid'
    ];

    protected $casts=[
        'details'=>'encrypted:json'
    ];

    protected static function boot()
    {
        parent::boot();

//
        self::creating(function($model){
//            $model->id=rand(10,10000);
        });
    }
}
