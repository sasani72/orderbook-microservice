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
        Schema::create('order_book', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('order_id');
            $table->string('symbol');
            $table->enum('side', ['buy', 'sell']);
            $table->decimal('price', 16, 10);
            $table->decimal('quantity', 16, 10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_book');
    }
};
