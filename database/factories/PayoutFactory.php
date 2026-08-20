<?php

namespace Database\Factories;

use App\Enums\PayoutStatus;
use App\Models\Payout;
use Illuminate\Database\Eloquent\Factories\Factory;

class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition()
    {
        return [
            'transaction_id' => $this->faker->unique()->bothify('TXN########'),
            'beneficiary_name' => $this->faker->name,
            'amount' => $this->faker->randomFloat(2, 1, 5000),
            'status' => PayoutStatus::PENDING,
        ];
    }
}
