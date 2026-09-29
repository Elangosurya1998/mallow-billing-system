<?php

declare(strict_types=1);

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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('currency', 3)->default('USD');
            $table->bigInteger('credit_balance_cents')->default(0);
            $table->string('external_reference')->nullable();
            $table->string('timezone')->default('UTC');
            $table->timestamps();

            $table->index(['merchant_id', 'email']);
            $table->index(['merchant_id', 'external_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
