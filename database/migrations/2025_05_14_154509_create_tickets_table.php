<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // Link to order (assuming each ticket is tied to one order)
            $table->foreignId('order_id')
                ->constrained('orders')
                ->onDelete('cascade');

            // Unique ticket code (can be used as QR code identifier)
            $table->string('ticket_code')->unique();

            // Type of ticket (VIP, Regular, etc.)
            $table->foreignId('ticket_type_id')
                ->constrained('ticket_types')
                ->onDelete('cascade');

            // The user who owns the ticket
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');

            // When the ticket is scanned
            $table->timestamp('scanned_at')->nullable();

            // Ticket status
            $table->enum('status', ['active', 'used', 'expired'])->default('active');

            // The event the ticket is for
            $table->foreignId('event_id')
                ->constrained('events')
                ->onDelete('cascade');

            // Laravel timestamps: created_at and updated_at
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
