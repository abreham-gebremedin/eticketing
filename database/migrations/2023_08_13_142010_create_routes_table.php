<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRoutesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('routes', function (Blueprint $table) {
            $table->id('RouteID');
            $table->string('DepartureCity', 100);
            $table->string('ArrivalCity', 100);
            $table->decimal('DistanceKM', 10, 2);
            $table->decimal('TicketPrice', 10, 2);
            $table->unsignedBigInteger('TicketOfficerID')->nullable();
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
        Schema::dropIfExists('routes');
    }
}
