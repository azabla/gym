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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict')->onUpdate('cascade'); // Foreign key to users table
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null')->onUpdate('cascade'); // Foreign key to packages table, nullable if no package is assigned
            $table->integer('duration_value')->default(1); // Duration value (e.g., 1 month, 3 weeks) the amount of time the member is payed for the gym
            $table->date('starting_date')->nullable(); // Date when the membership starts
            $table->date('valid_from')->nullable(); // Date when the membership is valid from
            $table->date('valid_until')->nullable(); // Date when the membership expires
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active'); // active, inactive, suspended, etc.
            $table->string('emergency_contact_name')->nullable(); // Name of the emergency contact
            $table->string('emergency_contact_phone')->nullable(); // Phone number of the emergency contact
            $table->string('membership_id')->unique(); // Unique membership ID for the member
            $table->text('notes')->nullable(); // Additional notes or comments about the member
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
