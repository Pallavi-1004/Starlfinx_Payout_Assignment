<?php

namespace App\Services;

use App\Enums\PayoutStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Balance;
use App\Models\Payout;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PayoutService
{
    public function create(array $attributes)
    {
        return DB::transaction(function () use ($attributes) {
            $balance = Balance::query()->lockForUpdate()->first();

            if (!$balance || (float) $balance->balance < (float) $attributes['amount']) {
                throw new InsufficientBalanceException();
            }

            $balance->balance = (float) $balance->balance - (float) $attributes['amount'];
            $balance->save();

            return Payout::create([
                'transaction_id' => $attributes['transaction_id'],
                'beneficiary_name' => $attributes['beneficiary_name'],
                'amount' => $attributes['amount'],
                'status' => PayoutStatus::PENDING,
            ]);
        });
    }

    public function updateStatus(Payout $payout, $newStatus)
    {
        if ($payout->status !== PayoutStatus::PENDING || !in_array($newStatus, [PayoutStatus::SUCCESS, PayoutStatus::FAILED], true)) {
            throw new InvalidArgumentException('Only pending payouts can be marked as success or failed.');
        }

        return DB::transaction(function () use ($payout, $newStatus) {
            $payout = Payout::query()->lockForUpdate()->findOrFail($payout->id);

            if ($payout->status !== PayoutStatus::PENDING) {
                throw new InvalidArgumentException('Only pending payouts can be marked as success or failed.');
            }

            if ($newStatus === PayoutStatus::FAILED) {
                $balance = Balance::query()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
                $balance->balance = (float) $balance->balance + (float) $payout->amount;
                $balance->save();
            }

            $payout->status = $newStatus;
            $payout->save();

            return $payout->fresh();
        });
    }
}
