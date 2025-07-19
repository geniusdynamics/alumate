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
        Schema::table('job_applications', function (Blueprint $table) {
            // Enhance status workflow management - PostgreSQL compatible
            $table->string('status')->default('pending')->change();
            
            // Add status history tracking
            $table->json('status_history')->nullable()->after('status');
            $table->timestamp('status_changed_at')->nullable()->after('status_history');
            $table->unsignedBigInteger('status_changed_by')->nullable()->after('status_changed_at');
            
            // Add application details
            $table->json('resume_data')->nullable()->after('cover_letter');
            $table->string('resume_file_path')->nullable()->after('resume_data');
            $table->json('additional_documents')->nullable()->after('resume_file_path');
            
            // Add interview and assessment information
            $table->timestamp('interview_scheduled_at')->nullable()->after('additional_documents');
            $table->string('interview_location')->nullable()->after('interview_scheduled_at');
            $table->text('interview_notes')->nullable()->after('interview_location');
            $table->json('assessment_scores')->nullable()->after('interview_notes');
            
            // Add employer feedback and rating
            $table->text('employer_feedback')->nullable()->after('assessment_scores');
            $table->integer('employer_rating')->nullable()->after('employer_feedback');
            $table->text('rejection_reason')->nullable()->after('employer_rating');
            
            // Add offer details
            $table->decimal('offered_salary', 10, 2)->nullable()->after('rejection_reason');
            $table->date('offer_expiry_date')->nullable()->after('offered_salary');
            $table->json('offer_terms')->nullable()->after('offer_expiry_date');
            
            // Add graduate response
            $table->text('graduate_response')->nullable()->after('offer_terms');
            $table->timestamp('graduate_responded_at')->nullable()->after('graduate_response');
            
            // Add matching and recommendation scores
            $table->decimal('match_score', 5, 2)->nullable()->after('graduate_responded_at');
            $table->json('matching_factors')->nullable()->after('match_score');
            
            // Add communication tracking
            $table->integer('messages_count')->default(0)->after('matching_factors');
            $table->timestamp('last_message_at')->nullable()->after('messages_count');
            
            // Add application source tracking - PostgreSQL compatible
            $table->string('application_source')->default('direct')->after('last_message_at');
            
            // Add priority and flagging - PostgreSQL compatible
            $table->string('priority')->default('normal')->after('application_source');
            $table->boolean('is_flagged')->default(false)->after('priority');
            $table->text('flag_reason')->nullable()->after('is_flagged');
            
            // Add foreign key for status changer
            $table->foreign('status_changed_by')->references('id')->on('users')->onUpdate('cascade')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_applications', function (Blueprint $table) {
            $table->dropForeign(['status_changed_by']);
            $table->dropColumn([
                'status_history',
                'status_changed_at',
                'status_changed_by',
                'resume_data',
                'resume_file_path',
                'additional_documents',
                'interview_scheduled_at',
                'interview_location',
                'interview_notes',
                'assessment_scores',
                'employer_feedback',
                'employer_rating',
                'rejection_reason',
                'offered_salary',
                'offer_expiry_date',
                'offer_terms',
                'graduate_response',
                'graduate_responded_at',
                'match_score',
                'matching_factors',
                'messages_count',
                'last_message_at',
                'application_source',
                'priority',
                'is_flagged',
                'flag_reason'
            ]);
            
            // Revert status to simple enum
            $table->string('status')->change();
        });
    }
};