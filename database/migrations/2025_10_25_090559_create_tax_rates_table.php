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
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('country', 2); // 2-letter country code (US, CA, IN, etc.)
            $table->string('state', 10)->nullable(); // State/province code
            $table->string('city')->nullable(); // Optional city name
            $table->string('postal_code')->nullable(); // Optional postal code
            $table->decimal('rate', 8, 6); // Tax rate (e.g., 0.08 for 8%)
            $table->string('type', 50); // Tax type (sales_tax, vat, gst, etc.)
            $table->string('name'); // Display name (e.g., "California Sales Tax")
            $table->string('description')->nullable(); // Additional description
            $table->boolean('is_active')->default(true); // Active status
            $table->date('effective_from')->nullable(); // When this rate becomes effective
            $table->date('effective_to')->nullable(); // When this rate expires
            $table->integer('priority')->default(0); // Priority for overlapping rules
            $table->timestamps();
            
            // Indexes for fast lookups
            $table->index(['country', 'state', 'is_active']);
            $table->index(['country', 'is_active']);
            $table->index('effective_from');
            $table->index('effective_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_rates');
    }
};
