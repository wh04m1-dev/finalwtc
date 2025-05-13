<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
       Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('event_name');
        $table->text('event_description')->nullable();
        $table->dateTime('event_date');
        $table->string('event_location');
        $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
        $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
