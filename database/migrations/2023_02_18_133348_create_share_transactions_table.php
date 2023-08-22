<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShareTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('share_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->date('date');
            $table->unsignedBigInteger('shareholder_id');
            $table->integer('shares_bought')->default(0);
            $table->integer('shares_sold')->default(0);
            $table->float('price_per_share');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('share_transactions');
    }
}
