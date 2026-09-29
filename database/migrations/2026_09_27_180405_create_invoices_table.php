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
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('merchant_id')->constrained('merchants')->cascadeOnDelete();
            $table->foreignUuid('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignUuid('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignUuid('subscription_period_id')->nullable()->constrained('subscription_periods')->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft'); // draft, open, paid, payment_failed, void, uncollectible
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('tax_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->bigInteger('amount_paid_cents')->default(0);
            $table->bigInteger('amount_remaining_cents')->default(0);
            $table->timestamp('due_date');
            $table->timestamp('paid_at')->nullable();
            $table->string('billing_reason')->default('subscription_cycle');
            $table->timestamps();

            $table->index(['merchant_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
