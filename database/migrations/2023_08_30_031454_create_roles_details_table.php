<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles_details', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('rol_id');


            $table->timestamps();

            $table->foreign('menu_id')->references('id')->on('menus');
            $table->foreign('rol_id')->references('id')->on('roles');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles_details');
    }
};
