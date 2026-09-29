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
        Schema::create('daily_usage_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('metric_identifier', 64);
            $table->date('usage_date');
            $table->unsignedBigInteger('total_quantity')->default(0);
            $table->unsignedBigInteger('event_count')->default(0);
            $table->timestamp('last_aggregated_at')->useCurrent();
            $table->timestamps();

            $table->unique(['customer_id', 'metric_identifier', 'usage_date'], 'daily_usage_cust_metric_date_unique');
            $table->index(['merchant_id', 'usage_date'], 'daily_usage_merchant_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_usage_summaries');
    }
};
