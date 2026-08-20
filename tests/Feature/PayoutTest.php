<?php

namespace Tests\Feature;

use App\Enums\PayoutStatus;
use App\Models\Balance;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Balance::create(['balance' => 100000]);
    }

    public function test_payout_is_created_and_balance_is_reduced()
    {
        $response = $this->postJson('/api/payouts', ['transaction_id' => 'TXN10001', 'beneficiary_name' => 'John Doe', 'amount' => 5000]);

        $response->assertStatus(201)->assertJsonPath('data.status', PayoutStatus::PENDING);
        $this->assertDatabaseHas('payouts', ['transaction_id' => 'TXN10001', 'status' => PayoutStatus::PENDING]);
        $this->assertDatabaseHas('balances', ['balance' => '95000.00']);
    }

    public function test_duplicate_transaction_id_is_rejected()
    {
        Payout::factory()->create(['transaction_id' => 'TXN10001']);
        $this->postJson('/api/payouts', ['transaction_id' => 'TXN10001', 'beneficiary_name' => 'Jane', 'amount' => 100])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_non_positive_amount_is_rejected()
    {
        $this->postJson('/api/payouts', ['transaction_id' => 'TXN10002', 'beneficiary_name' => 'Jane', 'amount' => 0])->assertStatus(422)->assertJsonValidationErrors(['amount']);
    }

    public function test_insufficient_balance_does_not_create_payout_or_change_balance()
    {
        Balance::query()->update(['balance' => 2000]);
        $this->postJson('/api/payouts', ['transaction_id' => 'TXN10003', 'beneficiary_name' => 'Jane', 'amount' => 5000])->assertStatus(422)->assertJsonPath('message', 'Insufficient balance');
        $this->assertDatabaseMissing('payouts', ['transaction_id' => 'TXN10003']);
        $this->assertDatabaseHas('balances', ['balance' => '2000.00']);
    }

    public function test_pending_payout_status_can_be_updated()
    {
        $payout = Payout::factory()->create();
        $this->patchJson('/api/payouts/' . $payout->id . '/status', ['status' => PayoutStatus::SUCCESS])->assertOk()->assertJsonPath('data.status', PayoutStatus::SUCCESS);
        $this->assertDatabaseHas('payouts', ['id' => $payout->id, 'status' => PayoutStatus::SUCCESS]);
    }

    public function test_completed_payout_cannot_change_status()
    {
        $payout = Payout::factory()->create(['status' => PayoutStatus::SUCCESS]);
        $this->patchJson('/api/payouts/' . $payout->id . '/status', ['status' => PayoutStatus::FAILED])->assertStatus(422);
    }

    public function test_payout_list_supports_search_filter_and_pagination()
    {
        Payout::factory()->count(11)->create();
        Payout::factory()->create(['transaction_id' => 'SPECIAL-TXN', 'status' => PayoutStatus::SUCCESS]);
        $response = $this->getJson('/api/payouts?transaction_id=SPECIAL&status=SUCCESS');
        $response->assertOk()->assertJsonPath('data.0.transaction_id', 'SPECIAL-TXN')->assertJsonStructure(['data', 'links', 'meta']);
    }
}
