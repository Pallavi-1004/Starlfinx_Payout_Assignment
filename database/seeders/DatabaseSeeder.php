<?php

namespace Database\Seeders;

use App\Models\Balance;
use App\Models\Payout;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Balance::query()->updateOrCreate([], ['balance' => 100000]);
        Payout::factory()->count(20)->create();
    }
}
