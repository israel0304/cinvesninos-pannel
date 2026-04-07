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
        Schema::create('product_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stand_id')->constrained('stands')->onDelete('cascade');
            $table->unsignedBigInteger('sku_id')->nullable();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'delivered', 'rejected', 'cancelled'])->default('pending');
            $table->enum('type', ['normal', 'special'])->default('normal');
            $table->text('custom_description')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('delivered_at')->nullable();
            $table->foreignId('staff_id')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_requests');
    }
};
