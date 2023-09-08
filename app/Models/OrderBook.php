<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderBook extends Model
{
    use HasFactory;

    protected $table = 'order_book';

    protected $fillable = ['order_id', 'symbol', 'price', 'quantity', 'timestamp'];
}
