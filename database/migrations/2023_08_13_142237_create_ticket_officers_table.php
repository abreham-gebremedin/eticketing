<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketOfficersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ticket_officers', function (Blueprint $table) {
            $table->id('OfficerID');
            $table->string('name', 100);
            $table->string('email', 100);
            $table->string('phone', 100);
            $table->string('ContactDetails', 200);
            // Other columns as needed
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
        Schema::dropIfExists('ticket_officers');
    }
}
