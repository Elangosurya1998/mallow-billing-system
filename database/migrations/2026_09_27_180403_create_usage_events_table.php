<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('merchant_id');
            $table->uuid('customer_id');
            $table->string('metric_identifier', 64);
            $table->unsignedBigInteger('quantity')->default(1);
            $table->string('idempotency_key', 128);
            $table->dateTime('timestamp');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->primary(['id', 'timestamp']);

            // Composite index: (customer_id, timestamp)
            $table->index(['customer_id', 'timestamp'], 'usage_events_customer_timestamp_idx');
            $table->index(['merchant_id', 'timestamp'], 'usage_events_merchant_timestamp_idx');

            // Unique composite index: (customer_id, idempotency_key)
            // On partitioned MySQL/MariaDB, unique keys must incorporate the partitioning column (timestamp)
            if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
                $table->unique(['customer_id', 'idempotency_key', 'timestamp'], 'usage_events_cust_idemp_time_unique');
            } else {
                $table->unique(['customer_id', 'idempotency_key'], 'usage_events_cust_idemp_unique');
            }
        });

        // High-Scale Optimization: MySQL/MariaDB Range Partitioning by Timestamp (Quarterly/Monthly)
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'])) {
            DB::statement('
                ALTER TABLE usage_events PARTITION BY RANGE COLUMNS(`timestamp`) (
                    PARTITION p_2026_q1 VALUES LESS THAN ("2026-04-01 00:00:00"),
                    PARTITION p_2026_q2 VALUES LESS THAN ("2026-07-01 00:00:00"),
                    PARTITION p_2026_q3 VALUES LESS THAN ("2026-10-01 00:00:00"),
                    PARTITION p_2026_q4 VALUES LESS THAN ("2027-01-01 00:00:00"),
                    PARTITION p_future VALUES LESS THAN MAXVALUE
                )
            ');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('usage_events');
    }
};
