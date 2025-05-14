<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('order_status', ['Cart', 'Confirmed', 'Paid', 'Cancelled'])->default('Cart'); // Enum for order_status
            $table->timestamp('order_date')->useCurrent();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->enum('payment_status', ['Pending', 'Paid', 'Failed'])->default('Pending'); // Enum for payment_status
            $table->timestamp('purchased_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
