<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{

    protected $table = 'stocks';
    protected $primaryKey = 'sku';

    protected $fillable = [
        'sku',
        'qty'
    ];

    public $timestamps = false;
}
