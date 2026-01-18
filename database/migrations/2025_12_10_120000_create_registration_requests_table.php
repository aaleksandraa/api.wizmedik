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
        Schema::create('registration_requests', function (Blueprint $table) {
            $table->id();

            // Request type
            $table->enum('type', ['doctor', 'clinic'])->index();

            // Status tracking
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])->default('pending')->index();

            // User data (encrypted)
            $table->string('email')->unique();
            $table->string('password'); // Hashed
            $table->string('ime');
            $table->string('prezime')->nullable(); // Only for doctors

            // Professional data
            $table->string('naziv')->nullable(); // Clinic name
            $table->string('telefon');
            $table->text('adresa');
            $table->string('grad');
            $table->string('specijalnost')->nullable(); // For doctors
            $table->foreignId('specijalnost_id')->nullable()->constrained('specijalnosti')->onDelete('set null');

            // Verification
            $table->string('email_verification_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('verification_code', 6)->nullable(); // 6-digit code

            // Documents (for verification)
            $table->json('documents')->nullable(); // License, ID, etc.
            $table->text('message')->nullable(); // User's message

            // Admin review
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Security
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->integer('attempts')->default(0); // Failed verification attempts

            // Created user reference
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doktori')->onDelete('cascade');
            $table->foreignId('clinic_id')->nullable()->constrained('klinike')->onDelete('cascade');

            // Expiration
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'type']);
            $table->index('email_verification_token');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registration_requests');
    }
};
