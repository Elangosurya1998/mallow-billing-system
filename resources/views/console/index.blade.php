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

        <!-- Left Navigation Sidebar: Endpoints Catalog -->
        <aside class="lg:col-span-4 space-y-4">
            <div class="bg-slate-900/90 rounded-xl border border-slate-800 p-4 shadow-sm max-h-[calc(100vh-140px)] overflow-y-auto space-y-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-semibold text-slate-400 uppercase tracking-wider">
                        All System Endpoints
                    </h2>
                    <span class="text-[10px] bg-slate-800 text-slate-400 font-mono px-1.5 py-0.5 rounded">20 APIs</span>
                </div>

                <!-- Section 1: Usage Metering & Events -->
                <div class="space-y-1">
                    <div class="text-[11px] font-semibold text-blue-400 flex items-center gap-1.5 px-1 py-1">
                        <span>📊</span> Usage Metering
                    </div>
                    <button type="button" onclick="switchEndpoint('post_usage')" id="nav-btn-post_usage" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between bg-blue-600/20 text-blue-400 border border-blue-500/40">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/api/v1/usage</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Idempotent</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_usage_batch')" id="nav-btn-post_usage_batch" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/usage/batch</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Bulk Ingestion</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_usage_summary')" id="nav-btn-get_usage_summary" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/usage/summary</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Rollups</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_usage_events')" id="nav-btn-get_usage_events" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/usage/events</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Raw Scans</span>
                    </button>
                </div>

                <!-- Section 2: Customer Accounts -->
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-emerald-400 flex items-center gap-1.5 px-1 py-1">
                        <span>👥</span> Customer Accounts
                    </div>
                    <button type="button" onclick="switchEndpoint('get_customers')" id="nav-btn-get_customers" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/api/v1/customers</span>
                        </span>
                        <span class="text-[10px] text-slate-400">List Scoped</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_customer')" id="nav-btn-post_customer" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/api/v1/customers</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Enroll Customer</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_customer_detail')" id="nav-btn-get_customer_detail" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/customers/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Profile</span>
                    </button>
                </div>

                <!-- Section 3: Merchant & Tenant Operations -->
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-purple-400 flex items-center gap-1.5 px-1 py-1">
                        <span>🏢</span> Merchants & Tenants
                    </div>
                    <button type="button" onclick="switchEndpoint('post_merchant')" id="nav-btn-post_merchant" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/api/v1/merchants</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Register</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_merchant')" id="nav-btn-get_merchant" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/merchants/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Profile</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('put_merchant')" id="nav-btn-put_merchant" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-amber-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">PUT</span>
                            <span class="font-mono truncate">/merchants/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Update</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_merchant_dashboard')" id="nav-btn-get_merchant_dashboard" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/dashboard (JSON)</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Analytics</span>
                    </button>
                </div>

                <!-- Section 4: Plans Catalog -->
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-amber-400 flex items-center gap-1.5 px-1 py-1">
                        <span>📦</span> Plans Catalog
                    </div>
                    <button type="button" onclick="switchEndpoint('get_plans')" id="nav-btn-get_plans" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/api/v1/plans</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Redis Cache</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_plan')" id="nav-btn-post_plan" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/api/v1/plans</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Create Plan</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_plan_detail')" id="nav-btn-get_plan_detail" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/plans/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Tiers & Quota</span>
                    </button>
                </div>

                <!-- Section 5: Subscriptions -->
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-cyan-400 flex items-center gap-1.5 px-1 py-1">
                        <span>🔄</span> Subscriptions
                    </div>
                    <button type="button" onclick="switchEndpoint('patch_subscription')" id="nav-btn-patch_subscription" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-amber-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">PATCH</span>
                            <span class="font-mono truncate">/subscriptions/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Plan Switch</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_subscription')" id="nav-btn-get_subscription" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/subscriptions/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">State</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_cancel_subscription')" id="nav-btn-post_cancel_subscription" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-rose-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/cancel</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Terminate</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_resume_subscription')" id="nav-btn-post_resume_subscription" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/resume</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Reactivate</span>
                    </button>
                </div>

                <!-- Section 6: Invoices & Payments -->
                <div class="space-y-1 pt-2 border-t border-slate-800">
                    <div class="text-[11px] font-semibold text-rose-400 flex items-center gap-1.5 px-1 py-1">
                        <span>🧾</span> Invoices & Payments
                    </div>
                    <button type="button" onclick="switchEndpoint('get_invoices')" id="nav-btn-get_invoices" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/api/v1/invoices</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Cycle Log</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('get_invoice_detail')" id="nav-btn-get_invoice_detail" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-blue-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">GET</span>
                            <span class="font-mono truncate">/invoices/{id}</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Line Items</span>
                    </button>
                    <button type="button" onclick="switchEndpoint('post_pay_invoice')" id="nav-btn-post_pay_invoice" class="w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent">
                        <span class="flex items-center gap-2">
                            <span class="bg-emerald-600 text-white font-bold px-1.5 py-0.5 rounded text-[10px]">POST</span>
                            <span class="font-mono truncate">/invoices/{id}/pay</span>
                        </span>
                        <span class="text-[10px] text-slate-400">Settle</span>
                    </button>
                </div>
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
                    <div class="flex items-center gap-2" id="endpoint-meta-badge">
                        <span class="text-xs text-slate-400">Rate Limit:</span>
                        <span class="text-xs font-mono bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">120 req/min</span>
                    </div>
                </div>

                <!-- FORM 1: POST /api/v1/usage (Usage Ingestion) -->
                <form id="form-post_usage" onsubmit="event.preventDefault(); submitUsageEvent();" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Customer Account</label>
                            <select id="usage-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                @forelse($selected_merchant->customers as $customer)
                                    <option value="{{ $customer->id }}">
                                        {{ $customer->name }} ({{ substr($customer->id, 0, 8) }}...)
                                    </option>
                                @empty
                                    <option value="">No customers enrolled</option>
                                @endforelse
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Metric Identifier</label>
                            <select id="usage-metric-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                <option value="api_requests" selected>api_requests (Metered API Calls)</option>
                                <option value="compute_seconds">compute_seconds (CPU Execution)</option>
                                <option value="storage_gb">storage_gb (Cloud Storage)</option>
                                <option value="database_queries">database_queries (DB Read/Write)</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Consumption Units (Quantity)</label>
                            <input type="number" id="usage-quantity" value="25" min="1" step="1" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-xs font-medium text-slate-300">Idempotency Key</label>
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

                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Event Properties (JSON Metadata)</label>
                        <textarea id="usage-properties" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 font-mono">{"endpoint": "/v1/charge", "status_code": 200, "region": "ap-south-1"}</textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-2">
                        <div class="flex items-center gap-2">
                            <button type="button" onclick="quickFill(10)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">+10 Units</button>
                            <button type="button" onclick="quickFill(100)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">+100 Units</button>
                            <button type="button" onclick="quickFill(500)" class="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1.5 rounded border border-slate-700 transition">+500 Units</button>
                        </div>
                        <button type="submit" id="btn-submit-usage" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <span>Send Ingestion Request</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 2: POST /api/v1/usage/batch -->
                <form id="form-post_usage_batch" onsubmit="event.preventDefault(); submitUsageBatch();" class="space-y-4 hidden">
                    <p class="text-xs text-slate-400">
                        Ingest a batch of up to 1,000 metered usage events in a single transactional request.
                    </p>
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Batch JSON Payload (events array)</label>
                        <textarea id="batch-events-json" rows="6" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-blue-500 font-mono">[
  {
    "customer_id": "{{ $selected_merchant->customers->first()?->id }}",
    "metric_identifier": "api_requests",
    "quantity": 100,
    "idempotency_key": "batch_item_1_{{ time() }}",
    "timestamp": "{{ now()->toIso8601String() }}"
  },
  {
    "customer_id": "{{ $selected_merchant->customers->first()?->id }}",
    "metric_identifier": "compute_seconds",
    "quantity": 300,
    "idempotency_key": "batch_item_2_{{ time() }}",
    "timestamp": "{{ now()->toIso8601String() }}"
  }
]</textarea>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Ingest Batch Events</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 3: GET /api/v1/usage/summary -->
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

                <!-- FORM 4: GET /api/v1/usage/events -->
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

                <!-- FORM 5: GET /api/v1/customers -->
                <form id="form-get_customers" onsubmit="event.preventDefault(); submitGetCustomers();" class="space-y-4 hidden">
                    <p class="text-xs text-slate-400">
                        Returns paginated customer accounts belonging strictly to <strong class="text-white">{{ $selected_merchant->name }}</strong> via <span class="font-mono text-emerald-400">X-Tenant-ID</span> header.
                    </p>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>List Tenant Customers</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 6: POST /api/v1/customers -->
                <form id="form-post_customer" onsubmit="event.preventDefault(); submitCreateCustomer();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Company / Customer Name</label>
                            <input type="text" id="cust-create-name" value="Starlight Enterprise" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Billing Email</label>
                            <input type="email" id="cust-create-email" value="billing@starlight.io" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Initial Credit Balance (USD)</label>
                            <input type="number" id="cust-create-credit" value="50.00" step="0.01" min="0" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">External Ref (ERP/CRM)</label>
                            <input type="text" id="cust-create-ref" value="ERP-9021" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Initial Plan (Optional)</label>
                            <select id="cust-create-plan" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                                <option value="">None (Enroll Unsubscribed)</option>
                                @foreach($selected_merchant->plans as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Register Customer via API</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 7: GET /api/v1/customers/{id} -->
                <form id="form-get_customer_detail" onsubmit="event.preventDefault(); submitGetCustomerDetail();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Select Customer</label>
                        <select id="detail-customer-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @foreach($selected_merchant->customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->id }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Customer Details</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 8: POST /api/v1/merchants -->
                <form id="form-post_merchant" onsubmit="event.preventDefault(); submitCreateMerchant();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Merchant Name</label>
                            <input type="text" id="merchant-create-name" value="Apex Cloud Services" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Slug (Unique identifier)</label>
                            <input type="text" id="merchant-create-slug" value="apex-cloud-{{ time() }}" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Billing Email</label>
                            <input type="email" id="merchant-create-email" value="billing@apexcloud.io" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Currency (ISO 4217)</label>
                            <input type="text" id="merchant-create-currency" value="USD" maxlength="3" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Timezone</label>
                            <input type="text" id="merchant-create-timezone" value="UTC" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Register New Merchant</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 9: GET /api/v1/merchants/{id} -->
                <form id="form-get_merchant" onsubmit="event.preventDefault(); submitGetMerchant();" class="space-y-4 hidden">
                    <p class="text-xs text-slate-400">
                        Fetches the tenant account record for the active merchant: <strong class="text-white">{{ $selected_merchant->name }}</strong>.
                    </p>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Merchant Profile</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 10: PUT /api/v1/merchants/{id} -->
                <form id="form-put_merchant" onsubmit="event.preventDefault(); submitUpdateMerchant();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Merchant Name</label>
                            <input type="text" id="merchant-edit-name" value="{{ $selected_merchant->name }}" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Slug</label>
                            <input type="text" id="merchant-edit-slug" value="{{ $selected_merchant->slug }}" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Email</label>
                            <input type="email" id="merchant-edit-email" value="{{ $selected_merchant->email }}" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Currency</label>
                            <input type="text" id="merchant-edit-currency" value="{{ $selected_merchant->currency }}" maxlength="3" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono uppercase">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Status</label>
                            <select id="merchant-edit-status" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                                <option value="active" {{ $selected_merchant->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $selected_merchant->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                <option value="suspended" {{ $selected_merchant->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-amber-600 hover:bg-amber-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Update Merchant (PUT)</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 11: GET /api/v1/merchants/{id}/dashboard -->
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

                <!-- FORM 12: GET /api/v1/plans -->
                <form id="form-get_plans" onsubmit="event.preventDefault(); submitGetPlans();" class="space-y-4 hidden">
                    <p class="text-xs text-slate-400">
                        Returns all active tiered plans. Responses are cached in Redis with a 10-minute TTL and flushed automatically upon updates.
                    </p>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Cached Plans Catalog</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 13: POST /api/v1/plans -->
                <form id="form-post_plan" onsubmit="event.preventDefault(); submitCreatePlan();" class="space-y-4 hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Plan Name</label>
                            <input type="text" id="plan-create-name" value="Ultra Scale Tier" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Slug</label>
                            <input type="text" id="plan-create-slug" value="ultra-scale-{{ time() }}" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Base Price (cents)</label>
                            <input type="number" id="plan-create-base-price" value="19900" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Interval</label>
                            <select id="plan-create-interval" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500">
                                <option value="month">Monthly</option>
                                <option value="year">Annual</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-300 mb-1">Trial Period Days</label>
                            <input type="number" id="plan-create-trial" value="14" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                        </div>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Create Plan & Invalidate Cache</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 14: GET /api/v1/plans/{id} -->
                <form id="form-get_plan_detail" onsubmit="event.preventDefault(); submitGetPlanDetail();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Select Plan</label>
                        <select id="detail-plan-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @foreach($selected_merchant->plans as $p)
                                <option value="{{ $p->id }}">{{ $p->name }} (${{ number_format($p->base_price_cents / 100, 2) }}/{{ $p->invoice_interval }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Get Plan Details</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 15: PATCH /api/v1/subscriptions/{id} (Mid-Cycle Switch) -->
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

                <!-- FORM 16: GET /api/v1/subscriptions/{id} -->
                <form id="form-get_subscription" onsubmit="event.preventDefault(); submitGetSubscription();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Subscription Record</label>
                        <select id="detail-sub-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @foreach($selected_merchant->subscriptions as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->customer?->name }} &middot; {{ $sub->plan?->name }} ({{ $sub->status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Query Subscription State</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 17: POST /api/v1/subscriptions/{id}/cancel -->
                <form id="form-post_cancel_subscription" onsubmit="event.preventDefault(); submitCancelSubscription();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Subscription to Cancel</label>
                        <select id="cancel-sub-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @foreach($selected_merchant->subscriptions as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->customer?->name }} &middot; {{ $sub->plan?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-rose-600 hover:bg-rose-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Cancel Subscription</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 18: POST /api/v1/subscriptions/{id}/resume -->
                <form id="form-post_resume_subscription" onsubmit="event.preventDefault(); submitResumeSubscription();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Subscription to Resume</label>
                        <select id="resume-sub-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @foreach($selected_merchant->subscriptions as $sub)
                                <option value="{{ $sub->id }}">{{ $sub->customer?->name }} &middot; {{ $sub->plan?->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Resume Subscription</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 19: GET /api/v1/invoices -->
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

                <!-- FORM 20: GET /api/v1/invoices/{id} -->
                <form id="form-get_invoice_detail" onsubmit="event.preventDefault(); submitGetInvoiceDetail();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Select Invoice</label>
                        <select id="detail-invoice-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @forelse($selected_merchant->invoices as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->invoice_number }} &middot; {{ $inv->status }} (${{ number_format($inv->total_cents / 100, 2) }})</option>
                            @empty
                                <option value="">No invoices generated yet</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Fetch Line Items & Breakdown</span>
                        </button>
                    </div>
                </form>

                <!-- FORM 21: POST /api/v1/invoices/{id}/pay -->
                <form id="form-post_pay_invoice" onsubmit="event.preventDefault(); submitPayInvoice();" class="space-y-4 hidden">
                    <div>
                        <label class="block text-xs font-medium text-slate-300 mb-1">Invoice to Settle / Pay</label>
                        <select id="pay-invoice-id" class="w-full bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:outline-none focus:border-blue-500 font-mono">
                            @forelse($selected_merchant->invoices as $inv)
                                <option value="{{ $inv->id }}">{{ $inv->invoice_number }} &middot; Status: {{ $inv->status }} (${{ number_format($inv->total_cents / 100, 2) }})</option>
                            @empty
                                <option value="">No invoices to pay</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="bg-emerald-600 hover:bg-emerald-500 text-white font-semibold text-xs px-5 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2">
                            <span>Process Payment (Pay Invoice)</span>
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
                    <pre id="response-json-display" class="bg-slate-950 text-slate-300 p-4 rounded-lg text-xs font-mono overflow-x-auto max-h-[440px] border border-slate-800">// Click "Send Ingestion Request" or choose any API from the catalog on the left to fire a live request...</pre>
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

        const allEndpoints = [
            'post_usage', 'post_usage_batch', 'get_usage_summary', 'get_usage_events',
            'get_customers', 'post_customer', 'get_customer_detail',
            'post_merchant', 'get_merchant', 'put_merchant', 'get_merchant_dashboard',
            'get_plans', 'post_plan', 'get_plan_detail',
            'patch_subscription', 'get_subscription', 'post_cancel_subscription', 'post_resume_subscription',
            'get_invoices', 'get_invoice_detail', 'post_pay_invoice'
        ];

        document.addEventListener('DOMContentLoaded', () => {
            generateNewKey();
        });

        function generateNewKey() {
            const key = "evt_web_" + Date.now() + "_" + Math.floor(Math.random() * 1000);
            const el = document.getElementById('usage-idemp-key');
            if (el) el.value = key;
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
            allEndpoints.forEach(f => {
                const formEl = document.getElementById('form-' + f);
                const navBtn = document.getElementById('nav-btn-' + f);
                if (!formEl || !navBtn) return;

                if (f === name) {
                    formEl.classList.remove('hidden');
                    navBtn.className = "w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between bg-blue-600/20 text-blue-400 border border-blue-500/40";
                } else {
                    formEl.classList.add('hidden');
                    navBtn.className = "w-full text-left px-2.5 py-2 rounded-lg text-xs font-medium transition flex items-center justify-between text-slate-300 hover:bg-slate-800 border border-transparent";
                }
            });

            const badge = document.getElementById('endpoint-method-badge');
            const path = document.getElementById('endpoint-path-display');
            const meta = document.getElementById('endpoint-meta-badge');

            const endpointConfig = {
                post_usage: { method: 'POST', path: '/api/v1/usage', meta: '120 req/min' },
                post_usage_batch: { method: 'POST', path: '/api/v1/usage/batch', meta: 'Batch 1000 events' },
                get_usage_summary: { method: 'GET', path: '/api/v1/usage/summary', meta: 'O(1) Rollup' },
                get_usage_events: { method: 'GET', path: '/api/v1/usage/events', meta: 'Partitioned' },
                get_customers: { method: 'GET', path: '/api/v1/customers', meta: 'Tenant-Scoped' },
                post_customer: { method: 'POST', path: '/api/v1/customers', meta: 'Tenant-Scoped' },
                get_customer_detail: { method: 'GET', path: '/api/v1/customers/{id}', meta: 'Tenant-Scoped' },
                post_merchant: { method: 'POST', path: '/api/v1/merchants', meta: 'Public' },
                get_merchant: { method: 'GET', path: '/api/v1/merchants/' + activeMerchantId, meta: 'Public' },
                put_merchant: { method: 'PUT', path: '/api/v1/merchants/' + activeMerchantId, meta: 'Public' },
                get_merchant_dashboard: { method: 'GET', path: '/api/v1/merchants/' + activeMerchantId + '/dashboard', meta: 'Analytics' },
                get_plans: { method: 'GET', path: '/api/v1/plans', meta: 'Redis 10m TTL' },
                post_plan: { method: 'POST', path: '/api/v1/plans', meta: 'Flushes Cache' },
                get_plan_detail: { method: 'GET', path: '/api/v1/plans/{id}', meta: 'Single Plan' },
                patch_subscription: { method: 'PATCH', path: '/api/v1/subscriptions/{id}', meta: 'Mid-Cycle Proration' },
                get_subscription: { method: 'GET', path: '/api/v1/subscriptions/{id}', meta: 'Subscription State' },
                post_cancel_subscription: { method: 'POST', path: '/api/v1/subscriptions/{id}/cancel', meta: 'State Transition' },
                post_resume_subscription: { method: 'POST', path: '/api/v1/subscriptions/{id}/resume', meta: 'State Transition' },
                get_invoices: { method: 'GET', path: '/api/v1/invoices', meta: 'Tenant-Scoped' },
                get_invoice_detail: { method: 'GET', path: '/api/v1/invoices/{id}', meta: 'Line Items' },
                post_pay_invoice: { method: 'POST', path: '/api/v1/invoices/{id}/pay', meta: 'Payment Engine' }
            };

            const cfg = endpointConfig[name] || { method: 'GET', path: '/api/v1', meta: '' };
            badge.innerText = cfg.method;
            path.innerText = cfg.path;
            meta.innerHTML = `<span class="text-xs text-slate-400">Context:</span><span class="text-xs font-mono bg-slate-800 text-slate-300 px-2 py-0.5 rounded border border-slate-700">${cfg.meta}</span>`;

            if (cfg.method === 'POST') badge.className = "bg-emerald-600 text-white font-bold px-2 py-0.5 rounded text-xs";
            else if (cfg.method === 'GET') badge.className = "bg-blue-600 text-white font-bold px-2 py-0.5 rounded text-xs";
            else if (cfg.method === 'PATCH' || cfg.method === 'PUT') badge.className = "bg-amber-600 text-white font-bold px-2 py-0.5 rounded text-xs";
            else badge.className = "bg-rose-600 text-white font-bold px-2 py-0.5 rounded text-xs";
        }

        // Submissions
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

            lastIdempKey = idempKey;
            await executeFetch('/api/v1/usage', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId },
                body: JSON.stringify({ customer_id: customerId, metric_identifier: metric, quantity, idempotency_key: idempKey, timestamp: new Date().toISOString(), properties })
            });
        }

        async function submitUsageBatch() {
            let events = [];
            try {
                events = JSON.parse(document.getElementById('batch-events-json').value);
            } catch (e) {
                alert("Invalid JSON array in batch payload!");
                return;
            }
            await executeFetch('/api/v1/usage/batch', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId },
                body: JSON.stringify({ events })
            });
        }

        async function submitSummaryQuery() {
            const customerId = document.getElementById('summary-customer-id').value;
            const metric = document.getElementById('summary-metric-id').value;
            let url = '/api/v1/usage/summary?customer_id=' + customerId;
            if (metric) url += '&metric_identifier=' + encodeURIComponent(metric);

            await executeFetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitEventsQuery() {
            const customerId = document.getElementById('events-customer-id').value;
            const limit = document.getElementById('events-limit').value || 20;
            await executeFetch('/api/v1/usage/events?customer_id=' + customerId + '&limit=' + limit, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitGetCustomers() {
            await executeFetch('/api/v1/customers', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitCreateCustomer() {
            const payload = {
                name: document.getElementById('cust-create-name').value,
                email: document.getElementById('cust-create-email').value,
                credit_balance: parseFloat(document.getElementById('cust-create-credit').value || 0),
                external_reference: document.getElementById('cust-create-ref').value,
            };
            const planId = document.getElementById('cust-create-plan').value;
            if (planId) payload.plan_id = planId;

            await executeFetch('/api/v1/customers', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId },
                body: JSON.stringify(payload)
            });
        }

        async function submitGetCustomerDetail() {
            const id = document.getElementById('detail-customer-id').value;
            await executeFetch('/api/v1/customers/' + id, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitCreateMerchant() {
            const payload = {
                name: document.getElementById('merchant-create-name').value,
                slug: document.getElementById('merchant-create-slug').value,
                email: document.getElementById('merchant-create-email').value,
                currency: document.getElementById('merchant-create-currency').value,
                timezone: document.getElementById('merchant-create-timezone').value,
            };
            await executeFetch('/api/v1/merchants', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey },
                body: JSON.stringify(payload)
            });
        }

        async function submitGetMerchant() {
            await executeFetch('/api/v1/merchants/' + activeMerchantId, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey }
            });
        }

        async function submitUpdateMerchant() {
            const payload = {
                name: document.getElementById('merchant-edit-name').value,
                slug: document.getElementById('merchant-edit-slug').value,
                email: document.getElementById('merchant-edit-email').value,
                currency: document.getElementById('merchant-edit-currency').value,
                timezone: 'UTC',
                status: document.getElementById('merchant-edit-status').value
            };
            await executeFetch('/api/v1/merchants/' + activeMerchantId, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey },
                body: JSON.stringify(payload)
            });
        }

        async function submitMerchantDashboard() {
            await executeFetch('/api/v1/merchants/' + activeMerchantId + '/dashboard', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey }
            });
        }

        async function submitGetPlans() {
            await executeFetch('/api/v1/plans', {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey }
            });
        }

        async function submitCreatePlan() {
            const payload = {
                name: document.getElementById('plan-create-name').value,
                slug: document.getElementById('plan-create-slug').value,
                base_price_cents: parseInt(document.getElementById('plan-create-base-price').value),
                invoice_interval: document.getElementById('plan-create-interval').value,
                trial_period_days: parseInt(document.getElementById('plan-create-trial').value),
                is_active: true
            };
            await executeFetch('/api/v1/plans', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey },
                body: JSON.stringify(payload)
            });
        }

        async function submitGetPlanDetail() {
            const id = document.getElementById('detail-plan-id').value;
            await executeFetch('/api/v1/plans/' + id, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey }
            });
        }

        async function submitPlanSwitch() {
            const subId = document.getElementById('switch-subscription-id').value;
            const newPlanId = document.getElementById('switch-new-plan-id').value;
            if (!subId || !newPlanId) {
                alert("Please select both a subscription and a target plan!");
                return;
            }
            await executeFetch('/api/v1/subscriptions/' + subId, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId },
                body: JSON.stringify({ plan_id: newPlanId })
            });
        }

        async function submitGetSubscription() {
            const id = document.getElementById('detail-sub-id').value;
            await executeFetch('/api/v1/subscriptions/' + id, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitCancelSubscription() {
            const id = document.getElementById('cancel-sub-id').value;
            await executeFetch('/api/v1/subscriptions/' + id + '/cancel', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitResumeSubscription() {
            const id = document.getElementById('resume-sub-id').value;
            await executeFetch('/api/v1/subscriptions/' + id + '/resume', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitInvoicesQuery() {
            const customerId = document.getElementById('invoices-customer-id').value;
            let url = '/api/v1/invoices';
            if (customerId) url += '?customer_id=' + customerId;
            await executeFetch(url, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitGetInvoiceDetail() {
            const id = document.getElementById('detail-invoice-id').value;
            await executeFetch('/api/v1/invoices/' + id, {
                method: 'GET',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
            });
        }

        async function submitPayInvoice() {
            const id = document.getElementById('pay-invoice-id').value;
            await executeFetch('/api/v1/invoices/' + id + '/pay', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-API-Key': apiKey, 'X-Tenant-ID': activeMerchantId }
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

            preEl.innerText = "// Sending request to " + url + "...";
            statusBadge.classList.add('hidden');
            timeBadge.classList.add('hidden');
            callout.classList.add('hidden');

            const startTime = performance.now();
            try {
                const response = await fetch(url, options);
                const duration = Math.round(performance.now() - startTime);
                const data = await response.json();

                statusBadge.classList.remove('hidden');
                statusBadge.innerText = response.status + " " + response.statusText;
                if (response.status >= 200 && response.status < 300) {
                    statusBadge.className = "text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-emerald-950 text-emerald-400 border border-emerald-800";
                } else {
                    statusBadge.className = "text-xs font-mono font-bold px-2.5 py-0.5 rounded bg-rose-950 text-rose-400 border border-rose-800";
                }

                timeBadge.classList.remove('hidden');
                timeBadge.innerText = duration + " ms";

                preEl.innerText = JSON.stringify(data, null, 2);

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
                statusBadge.innerText = "Network / Response Error";
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
