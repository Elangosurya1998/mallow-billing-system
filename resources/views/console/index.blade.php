<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Web Console & Playground — Mallow Billing System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #0f172a;
        }
        .code-font {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 antialiased min-h-screen flex flex-col">

    <!-- Top Header Navigation -->
    <header class="bg-[#1e2532] px-6 py-3.5 border-b border-slate-700 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-black text-white text-base shadow-sm">
                    M
                </div>
                <div>
                    <h1 class="text-white text-base font-bold tracking-tight leading-tight flex items-center gap-2">
                        API Web Console & Playground
                        <span class="text-[11px] font-mono font-normal bg-blue-950/80 text-blue-400 border border-blue-800/80 px-2 py-0.5 rounded">
                            Interactive Tester
                        </span>
                    </h1>
                    <p class="text-slate-400 text-xs font-medium">
                        Mallow Multi-Tenant Subscription & Usage Metering API
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Merchant Selector -->
                <div class="flex items-center gap-2 bg-slate-800/90 border border-slate-600 rounded px-2.5 py-1 text-xs">
                    <label for="console-merchant-switcher" class="text-slate-400 font-medium whitespace-nowrap">Active Merchant:</label>
                    <select id="console-merchant-switcher" onchange="window.location.href='/console?merchant_id=' + this.value;" class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer pr-1">
                        @foreach($merchants as $m)
                            <option value="{{ $m->id }}" {{ $selected_merchant->id === $m->id ? 'selected' : '' }} class="bg-slate-900 text-white">
                                {{ $m->name }} ({{ $m->currency }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <a href="{{ route('merchants.dashboard', $selected_merchant) }}" class="text-xs text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded border border-slate-600 transition flex items-center gap-1.5 font-medium">
                    <svg class="w-3.5 h-3.5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    View Dashboard
                </a>
                <a href="{{ route('merchants.index') }}" class="text-xs text-slate-300 hover:text-white bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded border border-slate-600 transition font-medium">
                    ← All Merchants
                </a>
            </div>
        </div>
    </header>

    <!-- Main Workspace Container -->
    <main class="max-w-7xl mx-auto px-6 py-6 flex-1 w-full grid grid-cols-1 lg:grid-cols-12 gap-6">

        <!-- Left Navigation Sidebar: Endpoints List -->
        <aside class="lg:col-span-4 space-y-4">
            <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-4 shadow-sm">
                <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-3">
                    API Endpoints Catalog
                </h2>

                <nav class="space-y-1.5" id="endpoint-nav">
                    <!-- Tab 1: POST /api/v1/usage -->
                    <button type="button" onclick="switchEndpoint('post_usage')" id="nav-btn-post_usage" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between bg-blue-600/20 text-blue-400 border border-blue-500/40">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono">/api/v1/usage</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Meter Event</span>
                    </button>

                    <!-- Tab 2: GET /api/v1/usage/summary -->
                    <button type="button" onclick="switchEndpoint('get_usage_summary')" id="nav-btn-get_usage_summary" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono">/api/v1/usage/summary</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Rollups</span>
                    </button>

                    <!-- Tab 3: GET /api/v1/usage/events -->
                    <button type="button" onclick="switchEndpoint('get_usage_events')" id="nav-btn-get_usage_events" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono">/api/v1/usage/events</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Raw Scans</span>
                    </button>

                    <!-- Tab 4: PATCH /api/v1/subscriptions/{id} (Mid-cycle switch) -->
                    <button type="button" onclick="switchEndpoint('patch_subscription')" id="nav-btn-patch_subscription" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-amber-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">PATCH</span>
                            <span class="font-mono">/api/v1/subscriptions</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Plan Switch</span>
                    </button>

                    <!-- Tab 5: GET /api/v1/invoices -->
                    <button type="button" onclick="switchEndpoint('get_invoices')" id="nav-btn-get_invoices" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono">/api/v1/invoices</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Billing History</span>
                    </button>

                    <!-- Tab 6: GET /api/v1/merchants/{id}/dashboard -->
                    <button type="button" onclick="switchEndpoint('get_merchant_dashboard')" id="nav-btn-get_merchant_dashboard" class="w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono">/dashboard (JSON)</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Executive</span>
                    </button>
                </nav>
            </div>

            <!-- Merchant Context Card -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-4 text-xs space-y-2.5">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                    Active Merchant Context
                </span>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Merchant Name:</span>
                    <span class="font-bold text-white">{{ $selected_merchant->name }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Currency:</span>
                    <span class="font-bold text-emerald-400">{{ $selected_merchant->currency }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-slate-400">Customer Count:</span>
                    <span class="font-bold text-slate-200">{{ $selected_merchant->customers->count() }} accounts</span>
                </div>
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <span class="text-[11px] text-slate-400 block font-medium">Merchant UUID:</span>
                    <div class="bg-slate-950 p-1.5 rounded font-mono text-[10px] text-slate-300 select-all truncate border border-slate-800">
                        {{ $selected_merchant->id }}
                    </div>
                </div>
                <div class="space-y-1">
                    <span class="text-[11px] text-slate-400 block font-medium">Default API Key:</span>
                    <div class="bg-slate-950 p-1.5 rounded font-mono text-[10px] text-emerald-400 select-all truncate border border-slate-800">
                        ak_test_acme_12345
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right Working Area: Form & Live Response Panel -->
        <section class="lg:col-span-8 space-y-6">

            <!-- Request Configuration Panel -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-5 shadow-sm space-y-5">
                <!-- Endpoint Header Bar -->
                <div class="flex flex-wrap items-center justify-between gap-3 pb-4 border-b border-slate-800">
                    <div class="flex items-center gap-2">
                        <span id="endpoint-method-badge" class="bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-xs">POST</span>
                        <span id="endpoint-path-display" class="font-mono text-sm font-bold text-slate-100">/api/v1/usage</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-400">Rate Limit:</span>
                        <span class="text-xs font-mono bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">120 req/min</span>
                    </div>
                </div>

                <!-- FORM: POST /api/v1/usage (Usage Ingestion) -->
                <form id="form-post_usage" onsubmit="event.preventDefault(); submitUsageEvent();" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Customer Dropdown Selector -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                Customer Account
                            </label>
                            <select id="usage-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @forelse($selected_merchant->customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }} ({{ substr($customer->id, 0, 8) }}...)
                                    </option>
                                @empty
                                    <option value="">No customers available</option>
                                @endforelse
                            </select>
                        </div>

                        <!-- Metric Identifier -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                Metric Identifier
                            </label>
                            <select id="usage-metric-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                <option value="api_requests" selected>api_requests (Metered API Calls)</option>
                                <option value="compute_seconds">compute_seconds (CPU Execution)</option>
                                <option value="storage_gb">storage_gb (Cloud Storage)</option>
                                <option value="database_queries">database_queries (DB Read/Write)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Quantity -->
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">
                                Consumption Units (Quantity)
                            </label>
                            <input type="number" id="usage-quantity" value="25" min="1" step="1" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>

                        <!-- Idempotency Key with Generators -->
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-medium text-slate-300">
                                    Idempotency Key
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" onclick="generateNewKey()" class="text-[10px] text-blue-400 hover:text-blue-300 underline cursor-pointer">
                                        🎲 New Key
                                    </button>
                                    <span class="text-slate-600 text-xs">|</span>
                                    <button type="button" onclick="replayLastKey()" class="text-[10px] text-amber-400 hover:text-amber-300 underline cursor-pointer" title="Re-submit identical key to test deduplication">
                                        🔁 Replay Last
                                    </button>
                                </div>
                            </div>
                            <input type="text" id="usage-idemp-key" value="" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>

                    <!-- Custom JSON Properties -->
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">
                            Event Properties (JSON Metadata)
                        </label>
                        <textarea id="usage-properties" rows="3" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 font-mono">{"endpoint": "/v1/charge", "status_code": 200, "region": "ap-south-1"}</textarea>
                    </div>

                    <!-- Action Controls -->
                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="quickFill(10)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">
                                +10 Units
                            </button>
                            <button type="button" onclick="quickFill(100)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">
                                +100 Units
                            </button>
                            <button type="button" onclick="quickFill(500)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">
                                +500 Units
                            </button>
                        </div>

                        <div class="flex items-center gap-3">
                            <button type="submit" id="btn-submit-usage" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                <span>Send Ingestion Request</span>
                            </button>
                        </div>
                    </div>
                </form>

                <!-- FORM: GET /api/v1/usage/summary -->
                <form id="form-get_usage_summary" onsubmit="event.preventDefault(); submitSummaryQuery();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Customer Account</label>
                            <select id="summary-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @foreach($selected_merchant->customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Metric Filter (Optional)</label>
                            <input type="text" id="summary-metric-id" value="api_requests" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Query Aggregated Summaries</span>
                        </button>
                    </div>
                </form>

                <!-- FORM: GET /api/v1/usage/events -->
                <form id="form-get_usage_events" onsubmit="event.preventDefault(); submitEventsQuery();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Customer Account</label>
                            <select id="events-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @foreach($selected_merchant->customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Record Limit</label>
                            <input type="number" id="events-limit" value="20" min="1" max="100" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Scan Partitioned Events</span>
                        </button>
                    </div>
                </form>

                <!-- FORM: PATCH /api/v1/subscriptions/{id} (Plan Switch) -->
                <form id="form-patch_subscription" onsubmit="event.preventDefault(); submitPlanSwitch();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Active Subscription</label>
                            <select id="switch-subscription-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @forelse($selected_merchant->subscriptions as $sub)
                                    <option value="{{ $sub->id }}">
                                        {{ $sub->customer?->name }} — {{ $sub->plan?->name }} ({{ substr($sub->id, 0, 8) }}...)
                                    </option>
                                @empty
                                    <option value="">No active subscriptions</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">New Plan Target</label>
                            <select id="switch-new-plan-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @foreach($selected_merchant->plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Quota: {{ number_format($p->included_units) }} units)</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Execute Mid-Cycle Plan Switch</span>
                        </button>
                    </div>
                </form>

                <!-- FORM: GET /api/v1/invoices -->
                <form id="form-get_invoices" onsubmit="event.preventDefault(); submitInvoicesQuery();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Filter by Customer (Optional)</label>
                        <select id="invoices-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            <option value="">All Merchant Customers</option>
                            @foreach($selected_merchant->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Invoices</span>
                        </button>
                    </div>
                </form>

                <!-- FORM: GET /api/v1/merchants/{id}/dashboard -->
                <form id="form-get_merchant_dashboard" onsubmit="event.preventDefault(); submitMerchantDashboard();" class="space-y-4 hidden">
                    <p class="text-xs text-slate-400">
                        Fetches the executive JSON analytics response computed across pre-aggregated daily summaries.
                    </p>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Merchant Analytics JSON</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Real-Time Response Viewer Panel -->
            <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                    <div class="flex items-center gap-3">
                        <h3 class="text-xs font-semibold text-slate-300 uppercase tracking-wider">
                            Live Response
                        </h3>
                        <span id="response-status-badge" class="hidden text-xs font-mono font-bold px-2.5 py-0.5 rounded"></span>
                        <span id="response-time-badge" class="hidden text-xs font-mono text-slate-400"></span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" onclick="copyResponseJson()" id="btn-copy-response" class="text-xs text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700 px-2.5 py-1 rounded transition border border-slate-700">
                            Copy JSON
                        </button>
                    </div>
                </div>

                <!-- Callout Banner for Idempotency Feedback -->
                <div id="idempotency-callout" class="hidden p-3 rounded-lg text-xs font-medium border flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span id="idempotency-icon" class="text-base"></span>
                        <span id="idempotency-message"></span>
                    </div>
                    <a href="{{ route('merchants.dashboard', $selected_merchant) }}" class="underline text-white font-semibold hover:text-blue-200">
                        View Updated Dashboard →
                    </a>
                </div>

                <!-- Raw JSON Code Display -->
                <div class="relative">
                    <pre id="response-json-display" class="bg-slate-950 text-slate-300 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-[420px] border border-slate-800">// Click "Send Ingestion Request" to fire an API request live...</pre>
                </div>
            </div>

        </section>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-800 bg-[#1e2532] py-3 px-6 text-center text-xs text-slate-400 mt-auto">
        <p>Mallow Billing System &mdash; API Web Console &middot; Strict Multi-Tenant Isolation &middot; 50L+ Range Partitioned Architecture</p>
    </footer>

    <!-- Interactive Client Script -->
    <script>
        const activeMerchantId = "{{ $selected_merchant->id }}";
        const apiKey = "ak_test_acme_12345";
        let lastIdempKey = "";

        // Initial setup on page load
        document.addEventListener('DOMContentLoaded', () => {
            generateNewKey();
        });

        function generateNewKey() {
            const key = "evt_web_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
            document.getElementById('usage-idemp-key').value = key;
        }

        function replayLastKey() {
            if (!lastIdempKey) {
                alert("No previous key to replay. Send a fresh request first!");
                return;
            }
            document.getElementById('usage-idemp-key').value = lastIdempKey;
        }

        function quickFill(quantity) {
            document.getElementById('usage-quantity').value = quantity;
            generateNewKey();
        }

        // Endpoint Switching
        function switchEndpoint(name) {
            const forms = ['post_usage', 'get_usage_summary', 'get_usage_events', 'patch_subscription', 'get_invoices', 'get_merchant_dashboard'];
            forms.forEach(f => {
                const formEl = document.getElementById('form-' + f);
                const navBtn = document.getElementById('nav-btn-' + f);
                if (f === name) {
                    formEl.classList.remove('hidden');
                    navBtn.className = "w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between bg-blue-600/20 text-blue-400 border border-blue-500/40";
                } else {
                    formEl.classList.add('hidden');
                    navBtn.className = "w-full text-left px-3 py-2.5 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent";
                }
            });

            // Update header badge
            const badge = document.getElementById('endpoint-method-badge');
            const path = document.getElementById('endpoint-path-display');
            if (name === 'post_usage') {
                badge.className = "bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "POST";
                path.innerText = "/api/v1/usage";
            } else if (name === 'get_usage_summary') {
                badge.className = "bg-blue-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "GET";
                path.innerText = "/api/v1/usage/summary";
            } else if (name === 'get_usage_events') {
                badge.className = "bg-blue-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "GET";
                path.innerText = "/api/v1/usage/events";
            } else if (name === 'patch_subscription') {
                badge.className = "bg-amber-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "PATCH";
                path.innerText = "/api/v1/subscriptions/{id}";
            } else if (name === 'get_invoices') {
                badge.className = "bg-blue-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "GET";
                path.innerText = "/api/v1/invoices";
            } else if (name === 'get_merchant_dashboard') {
                badge.className = "bg-blue-600 text-white font-bold px-2 py-0.5 rounded text-xs";
                badge.innerText = "GET";
                path.innerText = "/api/v1/merchants/" + activeMerchantId + "/dashboard";
            }
        }

        // 1. Submit Usage Event
        async function submitUsageEvent() {
            const customerId = document.getElementById('usage-customer-id').value;
            const metric = document.getElementById('usage-metric-id').value;
            const quantity = parseFloat(document.getElementById('usage-quantity').value);
            const idempKey = document.getElementById('usage-idemp-key').value;
            let properties = {};
            try {
                properties = JSON.parse(document.getElementById('usage-properties').value);
            } catch (e) {
                alert("Invalid JSON in Event Properties!");
                return;
            }

            const payload = {
                customer_id: customerId,
                metric_identifier: metric,
                quantity: quantity,
                idempotency_key: idempKey,
                timestamp: new Date().toISOString(),
                properties: properties
            };

            lastIdempKey = idempKey;

            await executeFetch('/api/v1/usage', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-API-Key': apiKey,
                    'X-Tenant-ID': activeMerchantId
                },
                body: JSON.stringify(payload)
            });
        }

        // 2. Submit Summary Query
        async function submitSummaryQuery() {
            const customerId = document.getElementById('summary-customer-id').value;
            const metric = document.getElementById('summary-metric-id').value;
            let url = '/api/v1/usage/summary?customer_id=' + customerId;
            if (metric) url += '&metric_identifier=' + encodeURIComponent(metric);

            await executeFetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': apiKey,
                    'X-Tenant-ID': activeMerchantId
                }
            });
        }

        // 3. Submit Events Query
        async function submitEventsQuery() {
            const customerId = document.getElementById('events-customer-id').value;
            const limit = document.getElementById('events-limit').value || 20;
            const url = '/api/v1/usage/events?customer_id=' + customerId + '&limit=' + limit;

            await executeFetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': apiKey,
                    'X-Tenant-ID': activeMerchantId
                }
            });
        }

        // 4. Submit Mid-Cycle Plan Switch
        async function submitPlanSwitch() {
            const subId = document.getElementById('switch-subscription-id').value;
            const newPlanId = document.getElementById('switch-new-plan-id').value;

            if (!subId || !newPlanId) {
                alert("Please select both a subscription and a target plan!");
                return;
            }

            await executeFetch('/api/v1/subscriptions/' + subId, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-API-Key': apiKey,
                    'X-Tenant-ID': activeMerchantId
                },
                body: JSON.stringify({
                    plan_id: newPlanId
                })
            });
        }

        // 5. Submit Invoices Query
        async function submitInvoicesQuery() {
            const customerId = document.getElementById('invoices-customer-id').value;
            let url = '/api/v1/invoices';
            if (customerId) url += '?customer_id=' + customerId;

            await executeFetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': apiKey,
                    'X-Tenant-ID': activeMerchantId
                }
            });
        }

        // 6. Submit Merchant Dashboard JSON Query
        async function submitMerchantDashboard() {
            const url = '/api/v1/merchants/' + activeMerchantId + '/dashboard';

            await executeFetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-API-Key': apiKey
                }
            });
        }

        // Universal Fetch Executor & Response Renderer
        async function executeFetch(url, options) {
            const preEl = document.getElementById('response-json-display');
            const statusBadge = document.getElementById('response-status-badge');
            const timeBadge = document.getElementById('response-time-badge');
            const callout = document.getElementById('idempotency-callout');
            const icon = document.getElementById('idempotency-icon');
            const msg = document.getElementById('idempotency-message');

            preEl.innerText = "// Sending request...";
            statusBadge.classList.add('hidden');
            timeBadge.classList.add('hidden');
            callout.classList.add('hidden');

            const startTime = performance.now();
            try {
                const response = await fetch(url, options);
                const duration = Math.round(performance.now() - startTime);
                const data = await response.json();

                // Status Badge
                statusBadge.classList.remove('hidden');
                statusBadge.innerText = response.status + " " + response.statusText;
                if (response.status >= 200 && response.status < 300) {
                    statusBadge.className = "text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800";
                } else {
                    statusBadge.className = "text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-rose-950 text-rose-400 border border-rose-800";
                }

                // Timing Badge
                timeBadge.classList.remove('hidden');
                timeBadge.innerText = duration + " ms";

                // Format JSON output
                preEl.innerText = JSON.stringify(data, null, 2);

                // Idempotency Callout Detection
                if (data.hasOwnProperty('idempotent_replay')) {
                    callout.classList.remove('hidden');
                    if (data.idempotent_replay === true) {
                        callout.className = "p-3 rounded-lg text-xs font-medium border border-blue-800 bg-blue-950/80 text-blue-200 flex items-center justify-between";
                        icon.innerText = "🔁";
                        msg.innerText = "Exact-Once Idempotency Verified! Duplicate key detected. HTTP 200 returned without double-counting units.";
                    } else {
                        callout.className = "p-3 rounded-lg text-xs font-medium border border-emerald-800 bg-emerald-950/80 text-emerald-200 flex items-center justify-between";
                        icon.innerText = "✅";
                        msg.innerText = "Fresh Event Ingested! HTTP 201 Created. Daily usage rollup atomically incremented.";
                    }
                }

            } catch (err) {
                const duration = Math.round(performance.now() - startTime);
                statusBadge.classList.remove('hidden');
                statusBadge.innerText = "Network / CORS Error";
                statusBadge.className = "text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-rose-950 text-rose-400 border border-rose-800";
                timeBadge.classList.remove('hidden');
                timeBadge.innerText = duration + " ms";
                preEl.innerText = err.toString();
            }
        }

        function copyResponseJson() {
            const text = document.getElementById('response-json-display').innerText;
            navigator.clipboard.writeText(text).then(() => {
                const btn = document.getElementById('btn-copy-response');
                const originalText = btn.innerText;
                btn.innerText = "Copied!";
                setTimeout(() => btn.innerText = originalText, 1500);
            });
        }
    </script>

</body>
</html>
