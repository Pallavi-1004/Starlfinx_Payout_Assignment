<?php

namespace App\Http\Controllers\Api;

use App\Enums\PayoutStatus;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayoutRequest;
use App\Http\Requests\UpdatePayoutStatusRequest;
use App\Http\Resources\PayoutResource;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class PayoutController extends Controller
{
    private $payoutService;

    public function __construct(PayoutService $payoutService)
    {
        $this->payoutService = $payoutService;
    }

    public function index(Request $request)
    {
        try {
            $payouts = Payout::query()
                ->when($request->input('transaction_id'), function ($query, $transactionId) {
                    $query->where('transaction_id', 'like', '%' . $transactionId . '%');
                })
                ->when($request->input('status'), function ($query, $status) {
                    $query->where('status', $status);
                })
                ->latest()
                ->paginate(10)
                ->appends($request->only(['transaction_id', 'status']));

            return PayoutResource::collection($payouts)->additional([
                'success' => true,
                'message' => 'Payouts retrieved successfully',
            ]);
        } catch (Throwable $exception) {
            Log::error('Payout listing failed', ['exception' => $exception]);
            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    public function store(StorePayoutRequest $request)
    {
        try {
            $payout = $this->payoutService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Payout created successfully',
                'data' => new PayoutResource($payout),
            ], 201);
        } catch (InsufficientBalanceException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            Log::error('Payout creation failed', ['exception' => $exception]);
            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again later.'], 500);
        }
    }

    public function updateStatus(UpdatePayoutStatusRequest $request, $id)
    {
        $payout = Payout::find($id);

        if (!$payout) {
            return response()->json(['success' => false, 'message' => 'Payout not found'], 404);
        }

        try {
            $updatedPayout = $this->payoutService->updateStatus($payout, $request->validated()['status']);

            return response()->json([
                'success' => true,
                'message' => 'Payout status updated successfully',
                'data' => new PayoutResource($updatedPayout),
            ]);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            Log::error('Payout status update failed', ['exception' => $exception]);
            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again later.'], 500);
        }
    }
}
