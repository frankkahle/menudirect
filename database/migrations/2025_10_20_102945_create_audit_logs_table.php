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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            // On this VM the "client" is a User (App\Models\Client is an alias for User);
            // there is no separate `clients` table, and no `domains` table at all.
            $table->foreignId('client_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // For admin actions
            $table->foreignId('domain_id')->nullable();

            $table->string('action', 100); // lock, unlock, transfer_start, epp_access, contact_update, etc.
            $table->string('resource_type', 50)->nullable(); // domain, contact, transfer, nameserver
            $table->unsignedBigInteger('resource_id')->nullable();

            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();

            $table->json('old_values')->nullable(); // Before state
            $table->json('new_values')->nullable(); // After state
            $table->text('description')->nullable(); // Human-readable description

            $table->timestamps();

            // Indexes for common queries
            $table->index(['client_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['domain_id', 'created_at']);
            $table->index('action');
            $table->index(['resource_type', 'resource_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
