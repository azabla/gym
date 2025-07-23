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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade')->onUpdate('cascade'); // Foreign key to users table
            $table->foreignId('member_id')->constrained('members')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null')->onUpdate('cascade'); // Foreign key to packages table, nullable if no package is assigned
            $table->decimal('amount', 10, 2); // Amount paid
            $table->enum('payment_method', ['cash', 'online'])->default('cash'); // Payment method used
            $table->date('payment_date')->nullable(); // Date of the payment
            $table->string('transaction_id')->nullable()->unique();
            $table->date('valid_from'); // Date when the payment is valid from
            $table->date('valid_until'); // Date when the payment expires or is valid until
            $table->text('notes')->nullable(); // Additional notes or comments about the payment
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
