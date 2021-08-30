<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockVendor extends Model
{

    protected $table = 'stock_vendor';
    protected $fillable = [
        'sku',
        'vendor'
    ];

    public $timestamps = false;
}
