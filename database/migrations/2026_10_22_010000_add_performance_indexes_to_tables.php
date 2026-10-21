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
        // Add performance indexes on messages table
        Schema::table('messages', function (Blueprint $table) {
            // Index for chat messages fetch & unread count
            $table->index('chat_id', 'idx_messages_chat_id');
            $table->index(['chat_id', 'created_at'], 'idx_messages_chat_created');
            $table->index(['chat_id', 'is_read'], 'idx_messages_chat_read');
        });

        // Add performance indexes on chats table
        Schema::table('chats', function (Blueprint $table) {
            $table->index('brand_id', 'idx_chats_brand_id');
            $table->index(['visitor_id', 'brand_id', 'status'], 'idx_chats_visitor_brand_status');
        });

        // Add performance index on visitors table
        Schema::table('visitors', function (Blueprint $table) {
            $table->index('last_seen_at', 'idx_visitors_last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('idx_messages_chat_id');
            $table->dropIndex('idx_messages_chat_created');
            $table->dropIndex('idx_messages_chat_read');
        });

        Schema::table('chats', function (Blueprint $table) {
            $table->dropIndex('idx_chats_brand_id');
            $table->dropIndex('idx_chats_visitor_brand_status');
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex('idx_visitors_last_seen_at');
        });
    }
};
