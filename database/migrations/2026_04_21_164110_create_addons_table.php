<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->boolean('is_recurring')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Package ↔ Addon pivot
        Schema::create('package_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->cascadeOnDelete();
            $table->decimal('price_override', 10, 2)->nullable();
            $table->timestamps();
        });

        // Member ↔ Addon pivot 
        Schema::create('member_addon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('addon_id')->constrained()->cascadeOnDelete();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->timestamps();
            $table->unique(['member_id', 'addon_id']);
        });

        // Add snapshot column to payments
        Schema::table('payments', function (Blueprint $table) {
            $table->json('addons')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_addon');
        Schema::dropIfExists('package_addon');
        Schema::dropIfExists('addons');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('addons');
        });
    }
};