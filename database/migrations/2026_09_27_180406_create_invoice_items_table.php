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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('description');
            $table->string('metric_identifier')->nullable();
            $table->bigInteger('quantity')->default(1);
            $table->bigInteger('unit_price_cents')->default(0);
            $table->bigInteger('subtotal_cents')->default(0);
            $table->boolean('is_proration')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['invoice_id', 'metric_identifier']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
