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
        Schema::create('subscription_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
            $table->foreignUuid('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->string('status')->default('active'); // active, billed, closed
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->bigInteger('prorated_base_price_cents')->default(0);
            $table->bigInteger('prorated_allowance_units')->default(0);
            $table->bigInteger('overage_rate_cents')->default(0);
            $table->timestamps();

            $table->index(['subscription_id', 'period_start', 'period_end'], 'sub_periods_dates_idx');
            $table->index(['subscription_id', 'status'], 'sub_periods_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_periods');
    }
};
