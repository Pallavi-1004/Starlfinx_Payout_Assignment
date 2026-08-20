<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Balance extends Model
{
    protected $fillable = ['balance'];

    protected $casts = [
        'balance' => 'decimal:2',
    ];
}
