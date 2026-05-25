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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['lost', 'found']);
            $table->string('category'); // Using string for flexibility, can be electronics, documents, etc.
            $table->text('description');
            $table->dateTime('date_time');
            $table->string('location');
            $table->string('image_url')->nullable();
            $table->string('contact_info');
            $table->enum('status', ['active', 'resolved'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
