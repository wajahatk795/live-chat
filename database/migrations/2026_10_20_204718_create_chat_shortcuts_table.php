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
        Schema::create('chat_shortcuts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brand')->nullOnDelete();
            $table->string('shortcut', 50);
            $table->string('title', 100);
            $table->text('content');
            $table->string('scope', 20);
            $table->timestamps();

            $table->index(['brand_id', 'scope']);
            $table->index(['user_id', 'scope']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_shortcuts');
    }
};
