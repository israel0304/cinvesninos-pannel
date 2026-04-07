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
        Schema::create('stands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->foreignId('stand_type_id')->constrained('stand_types');
            $table->string('activity_name')->nullable();
            $table->string('activity_category')->nullable();
            $table->string('project_video_url')->nullable();
            $table->boolean('uses_recycled_canvas')->default(false);
            $table->boolean('brings_own_equipment')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stands');
    }
};
