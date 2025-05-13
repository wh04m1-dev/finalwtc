<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade'); // References orders.id
            $table->enum('payment_method', ['Credit Card', 'PayPal', 'Bank Transfer'])->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->decimal('payment_amount', 10, 2);
            $table->enum('payment_status', ['Pending', 'Successful', 'Failed'])->default('Pending'); // Enum for payment_status
            $table->timestamps(); // Adds created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
