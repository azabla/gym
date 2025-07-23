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
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Unique name for the package
            $table->text('description')->nullable(); // Description of the package
            $table->decimal('price', 10, 2); // Price of the package
            $table->enum('duration_unit', ['day', 'week', 'month', 'year'])->default('month');
            // $table->integer('duration_value'); // Duration value (e.g., 1 month, 3 weeks)
            $table->string('image')->nullable(); // Image associated with the package
            $table->text('features')->nullable(); // Features included in the package, stored as JSON or text
            $table->enum('status', ['active', 'inactive'])->default('active'); // Status of the package
            $table->softDeletes(); // Soft delete column to mark the package as deleted without removing it from the database
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
