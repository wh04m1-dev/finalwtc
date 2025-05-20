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
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // Reference to order_detail
            $table->string('ticket_code')->unique(); // A unique code for each ticket (e.g., QR Code identifier)
            $table->foreignId('ticket_type_id')->constrained('ticket_types')->onDelete('cascade'); // Link to ticket type
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Link to the user who owns the ticket
            $table->timestamp('scanned_at')->nullable(); // When the ticket was scanned at the event
            $table->enum('status', ['active', 'used', 'expired'])->default('active'); // Ticket status
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
