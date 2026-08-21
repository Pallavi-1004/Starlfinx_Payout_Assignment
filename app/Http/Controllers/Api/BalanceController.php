<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Balance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class BalanceController extends Controller
{
    public function show()
    {
        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $this->currentBalance(),
            ],
        ]);
    }

    public function add(Request $request)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        try {
            $balance = DB::transaction(function () use ($validated) {
                $balance = Balance::query()->lockForUpdate()->firstOrCreate([], ['balance' => 0]);
                $balance->balance = (float) $balance->balance + (float) $validated['amount'];
                $balance->save();

                return $balance;
            });

            return response()->json([
                'success' => true,
                'message' => 'Balance added successfully',
                'data' => [
                    'balance' => $balance->balance,
                ],
            ]);
        } catch (Throwable $exception) {
            Log::error('Balance addition failed', ['exception' => $exception]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong. Please try again later.',
            ], 500);
        }
    }

    private function currentBalance()
    {
        return optional(Balance::query()->first())->balance ?? '0.00';
    }
}
