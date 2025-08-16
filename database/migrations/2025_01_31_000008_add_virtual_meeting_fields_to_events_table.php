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
        Schema::table('events', function (Blueprint $table) {
            // Jitsi Meet integration fields
            $table->string('jitsi_room_id')->nullable()->after('virtual_instructions');
            $table->text('meeting_url')->nullable()->after('jitsi_room_id');
            $table->enum('meeting_platform', ['jitsi', 'zoom', 'teams', 'google_meet', 'webex', 'other'])
                ->default('jitsi')->after('meeting_url');
            $table->text('meeting_password')->nullable()->after('meeting_platform');
            $table->boolean('meeting_embed_allowed')->default(true)->after('meeting_password');
            $table->boolean('recording_enabled')->default(false)->after('meeting_embed_allowed');

            // Additional virtual event fields
            $table->json('jitsi_config')->nullable()->after('recording_enabled');
            $table->json('meeting_metadata')->nullable()->after('jitsi_config');
            $table->boolean('waiting_room_enabled')->default(false)->after('meeting_metadata');
            $table->boolean('chat_enabled')->default(true)->after('waiting_room_enabled');
            $table->boolean('screen_sharing_enabled')->default(true)->after('chat_enabled');
            $table->text('meeting_instructions')->nullable()->after('screen_sharing_enabled');

            // Add indexes for performance
            $table->index('jitsi_room_id');
            $table->index('meeting_platform');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropIndex(['jitsi_room_id']);
            $table->dropIndex(['meeting_platform']);

            $table->dropColumn([
                'jitsi_room_id',
                'meeting_url',
                'meeting_platform',
                'meeting_password',
                'meeting_embed_allowed',
                'recording_enabled',
                'jitsi_config',
                'meeting_metadata',
                'waiting_room_enabled',
                'chat_enabled',
                'screen_sharing_enabled',
                'meeting_instructions',
            ]);
        });
    }
};
