<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('keyboard_data', function (Blueprint $table) {
            $table->id();
            $table->string('password');
            $table->integer('user_id');
            $table->integer('target_user_id');
            $table->json('press_times');
            $table->json('interval_times');
            $table->json('array_times');
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
        Schema::dropIfExists('keyboard_data');
    }
};
