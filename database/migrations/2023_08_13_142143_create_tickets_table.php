<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
{
    Schema::create('tickets', function (Blueprint $table) {
        $table->id('TicketID');
        $table->unsignedBigInteger('BusID');
        $table->unsignedBigInteger('RouteID');
        $table->unsignedBigInteger('TicketOfficerID');
        $table->integer('SeatNumber');
        $table->decimal('TicketPrice', 10, 2);
        $table->decimal('CommissionFee', 10, 2);
        $table->string('PaymentStatus', 20);
        $table->datetime('DepartureTime');
        $table->datetime('ArrivalTime');
        $table->foreign('BusID')->references('BusID')->on('buses');
        $table->foreign('RouteID')->references('RouteID')->on('routes');
        $table->foreign('TicketOfficerID')->references('OfficerID')->on('ticket_officers');
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
        Schema::dropIfExists('tickets');
    }
}
