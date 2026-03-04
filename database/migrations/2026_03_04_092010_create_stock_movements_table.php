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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->foreignId('destination_store_id')->nullable()->constrained('stores')->nullOnDelete();
            $table->integer('quantity');
            $table->string('type'); // sale, transfer, adjustment, procurement
            $table->string('reference')->nullable(); // Order ID, Invoice number, etc.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Responsible user
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
