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
        Schema::create('booking_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('tour_id')->nullable()->constrained()->onDelete('set null');
            $table->string('backup_type')->default('booking'); // 'booking', 'tour', 'full'
            $table->json('tour_data')->nullable(); // Full tour information snapshot
            $table->json('booking_data')->nullable(); // Full booking information snapshot
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();
            $table->string('backup_reason')->nullable(); // 'manual', 'auto', 'before_update', 'before_delete'
            $table->timestamp('restored_at')->nullable();
            $table->timestamps();
            
            $table->index('booking_id');
            $table->index('tour_id');
            $table->index('backup_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_backups');
    }
};
