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
        Schema::table('messages', function (Blueprint $table) {
            // Add new conversation-based messaging fields
            $table->foreignId('conversation_id')->nullable()->constrained('conversations')->onDelete('cascade');
            $table->json('attachments')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('reply_to_id')->nullable()->constrained('messages')->onDelete('set null');
            $table->boolean('is_edited')->default(false);
            $table->timestamp('edited_at')->nullable();
            $table->softDeletes(); // Add soft deletes
            
            // Add indexes for performance
            $table->index(['conversation_id', 'created_at']);
            $table->index('reply_to_id');
            
            // Add full-text search index for message content if it doesn't exist
            if (Schema::hasColumn('messages', 'content')) {
                $table->fullText('content');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn([
                'conversation_id',
                'attachments',
                'metadata',
                'reply_to_id',
                'is_edited',
                'edited_at'
            ]);
        });
    }
};
