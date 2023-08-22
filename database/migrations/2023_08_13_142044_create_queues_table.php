<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQueuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('queue', function (Blueprint $table) {
            $table->id('QueueID');
            $table->unsignedBigInteger('BusID');
            $table->unsignedBigInteger('RouteID');
            $table->integer('Position');
            $table->datetime('DepartureTime');
            $table->boolean('IsDeparted');
            $table->foreign('BusID')->references('BusID')->on('buses');
            $table->foreign('RouteID')->references('RouteID')->on('routes');
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
        Schema::dropIfExists('queues');
    }
}
