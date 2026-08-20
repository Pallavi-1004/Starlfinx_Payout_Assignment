<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayoutsTable extends Migration
{
    public function up()
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 100)->unique();
            $table->string('beneficiary_name', 150);
            $table->decimal('amount', 15, 2);
            $table->string('status', 20)->index();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('payouts');
    }
}
