<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\DailyUsageSummary;
use App\Models\Merchant;
use App\Models\UsageEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase2IngestionPipelineTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->merchant = Merchant::create([
            'name' => 'Acme Ingestion Corp',
            'slug' => 'acme-ingestion',
            'email' => 'ingest@acme.com',
            'currency' => 'USD',
        ]);

        $this->customer = Customer::create([
            'merchant_id' => $this->merchant->id,
            'name' => 'Starlight Media',
            'email' => 'media@starlight.io',
            'currency' => 'USD',
        ]);
    }

    public function test_fresh_usage_submission_returns_201_and_updates_daily_rollup(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 150,
            'idempotency_key' => 'idemp-req-001',
            'timestamp' => '2026-09-27T10:00:00Z',
            'properties' => [
                'endpoint' => '/api/v1/search',
                'status' => 200,
            ],
        ];

        $response = $this->withHeader('X-API-Key', 'test-key-alpha')
            ->postJson('/api/v1/usage', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_id', $this->customer->id)
            ->assertJsonPath('data.metric_identifier', 'api_requests')
            ->assertJsonPath('data.quantity', 150)
            ->assertJsonPath('data.idempotency_key', 'idemp-req-001')
            ->assertJsonPath('idempotent_replay', false);

        // Verify usage_events record
        $this->assertDatabaseHas('usage_events', [
            'customer_id' => $this->customer->id,
            'idempotency_key' => 'idemp-req-001',
            'quantity' => 150,
        ]);

        // Verify daily_usage_summaries rollup
        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('metric_identifier', 'api_requests')
            ->where('usage_date', '2026-09-27')
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(150, $summary->total_quantity);
        $this->assertSame(1, $summary->event_count);
    }

    public function test_duplicate_submission_matching_customer_and_idempotency_key_returns_200_without_double_counting(): void
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'compute_minutes',
            'quantity' => 45,
            'idempotency_key' => 'idemp-compute-identical-key',
            'timestamp' => '2026-09-27T12:00:00Z',
        ];

        // 1. Initial fresh submission -> 201 Created
        $response1 = $this->withHeader('X-API-Key', 'test-key-beta')
            ->postJson('/api/v1/usage', $payload);

        $response1->assertStatus(201);
        $eventId1 = $response1->json('data.id');

        // Verify initial daily rollup total = 45
        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('metric_identifier', 'compute_minutes')
            ->first();
        $this->assertSame(45, $summary->total_quantity);
        $this->assertSame(1, $summary->event_count);

        // 2. Duplicate submission with exact same (customer_id, idempotency_key) -> 200 OK
        $response2 = $this->withHeader('X-API-Key', 'test-key-beta')
            ->postJson('/api/v1/usage', $payload);

        $response2->assertStatus(200)
            ->assertJsonPath('data.id', $eventId1)
            ->assertJsonPath('data.idempotency_key', 'idemp-compute-identical-key')
            ->assertJsonPath('idempotent_replay', true);

        // Verify no second record was created
        $count = UsageEvent::where('customer_id', $this->customer->id)
            ->where('idempotency_key', 'idemp-compute-identical-key')
            ->count();
        $this->assertSame(1, $count);

        // CRITICAL: Verify daily rollup was NOT incremented (no double counting!)
        $summary->refresh();
        $this->assertSame(45, $summary->total_quantity);
        $this->assertSame(1, $summary->event_count);
    }

    public function test_submitting_different_events_accumulates_daily_aggregates(): void
    {
        // First event: 50 units
        $this->withHeader('X-API-Key', 'test-key-gamma')
            ->postJson('/api/v1/usage', [
                'customer_id' => $this->customer->id,
                'metric_identifier' => 'storage_gb',
                'quantity' => 50,
                'idempotency_key' => 'idemp-store-1',
                'timestamp' => '2026-09-27T08:00:00Z',
            ])->assertStatus(201);

        // Second event: 75 units on the same day
        $this->withHeader('X-API-Key', 'test-key-gamma')
            ->postJson('/api/v1/usage', [
                'customer_id' => $this->customer->id,
                'metric_identifier' => 'storage_gb',
                'quantity' => 75,
                'idempotency_key' => 'idemp-store-2',
                'timestamp' => '2026-09-27T16:00:00Z',
            ])->assertStatus(201);

        $summary = DailyUsageSummary::where('customer_id', $this->customer->id)
            ->where('metric_identifier', 'storage_gb')
            ->where('usage_date', '2026-09-27')
            ->first();

        $this->assertNotNull($summary);
        $this->assertSame(125, $summary->total_quantity); // 50 + 75 = 125
        $this->assertSame(2, $summary->event_count);
    }

    public function test_record_usage_validation_rules(): void
    {
        // 1. Missing customer_id
        $this->postJson('/api/v1/usage', [
            'metric_identifier' => 'api_requests',
            'quantity' => 10,
            'idempotency_key' => 'key-1',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);

        // 2. Non-existent customer_id
        $this->postJson('/api/v1/usage', [
            'customer_id' => '00000000-0000-0000-0000-000000000000',
            'metric_identifier' => 'api_requests',
            'quantity' => 10,
            'idempotency_key' => 'key-2',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id']);

        // 3. Zero or negative quantity
        $this->postJson('/api/v1/usage', [
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 0,
            'idempotency_key' => 'key-3',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);

        // 4. Missing idempotency_key
        $this->postJson('/api/v1/usage', [
            'customer_id' => $this->customer->id,
            'metric_identifier' => 'api_requests',
            'quantity' => 10,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['idempotency_key']);
    }

    public function test_rate_limiting_per_api_key_at_120_req_per_min(): void
    {
        $apiKey = 'api_key_limit_tester';

        // Perform 120 successful requests
        for ($i = 1; $i <= 120; $i++) {
            $response = $this->withHeader('X-API-Key', $apiKey)
                ->postJson('/api/v1/usage', [
                    'customer_id' => $this->customer->id,
                    'metric_identifier' => 'pings',
                    'quantity' => 1,
                    'idempotency_key' => "ping-{$i}",
                ]);

            $response->assertStatus(201);
        }

        // 121st request must trigger HTTP 429 Too Many Requests
        $overflowResponse = $this->withHeader('X-API-Key', $apiKey)
            ->postJson('/api/v1/usage', [
                'customer_id' => $this->customer->id,
                'metric_identifier' => 'pings',
                'quantity' => 1,
                'idempotency_key' => 'ping-121-overflow',
            ]);

        $overflowResponse->assertStatus(429);

        // A different API key should still have its quota
        $diffKeyResponse = $this->withHeader('X-API-Key', 'different_api_key')
            ->postJson('/api/v1/usage', [
                'customer_id' => $this->customer->id,
                'metric_identifier' => 'pings',
                'quantity' => 1,
                'idempotency_key' => 'ping-diff-key-1',
            ]);

        $diffKeyResponse->assertStatus(201);
    }
}
