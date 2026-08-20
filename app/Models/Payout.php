<?php

namespace App\Models;

use App\Enums\PayoutStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'beneficiary_name',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function getStatusAttribute($value)
    {
        return $value;
    }

    public function isPending()
    {
        return $this->status === PayoutStatus::PENDING;
    }
}
