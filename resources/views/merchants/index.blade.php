<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchants Directory — Mallow Billing System</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f8fafc;
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Top Header Navigation -->
    <header class="bg-[#1e2532] px-6 py-4 border-b border-slate-700 sticky top-0 z-50 shadow-md">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center font-black text-white text-base shadow-sm">
                    M
                </div>
                <div>
                    <h1 class="text-white text-base font-bold tracking-tight leading-tight">
                        Mallow Billing System
                    </h1>
                    <p class="text-slate-400 text-xs font-medium">
                        Multi-Tenant Subscription & Metering Engine
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('api.console') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    API Web Console
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-6 py-8 flex-1 w-full space-y-8">

        <!-- Page Introduction Banner -->
        <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-2 text-xs font-semibold text-blue-600 uppercase tracking-wider bg-blue-50 px-2.5 py-0.5 rounded-full">
                    Tenant Control Center
                </div>
                <h2 class="text-2xl font-bold text-slate-900 tracking-tight">
                    Select a Merchant to Switch Dashboard
                </h2>
                <p class="text-sm text-slate-500 max-w-2xl">
                    Each merchant operates in strict multi-tenant isolation with its own billing plans, currency configurations, customer usage quotas, and real-time overage telemetry. Click any merchant below to view its live executive dashboard.
                </p>
            </div>

            <!-- Quick Stats Counters -->
            <div class="flex items-center gap-4 shrink-0">
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 text-center min-w-[110px]">
                    <span class="text-2xl font-bold text-slate-900">{{ $merchants->count() }}</span>
                    <p class="text-xs font-medium text-slate-500">Merchants</p>
                </div>
                <div class="bg-slate-50 rounded-lg p-3 border border-slate-200 text-center min-w-[110px]">
                    <span class="text-2xl font-bold text-blue-600">{{ $merchants->sum('customers_count') }}</span>
                    <p class="text-xs font-medium text-slate-500">Customers</p>
                </div>
            </div>
        </div>

        <!-- Merchants Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($merchants as $merchant)
                @php
                    $activePlan = $merchant->plans->firstWhere('is_active', true) ?? $merchant->plans->first();
                    $initials = strtoupper(substr($merchant->name, 0, 2));
                    $currencySymbol = match(strtoupper($merchant->currency)) {
                        'USD', '$' => '$',
                        'EUR', '€' => '€',
                        'GBP', '£' => '£',
                        default => '₹',
                    };
                @endphp
                <div class="bg-white rounded-xl border border-slate-200 p-6 shadow-xs hover:shadow-md hover:border-blue-300 transition-all flex flex-col justify-between group">
                    <div>
                        <!-- Card Header -->
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-800 to-slate-900 text-white font-bold text-lg flex items-center justify-center shadow-xs group-hover:from-blue-600 group-hover:to-blue-700 transition">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-900 group-hover:text-blue-600 transition tracking-tight">
                                        {{ $merchant->name }}
                                    </h3>
                                    <span class="text-xs text-slate-500 font-mono">
                                        slug: {{ $merchant->slug }}
                                    </span>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                {{ ucfirst($merchant->status ?? 'active') }}
                            </span>
                        </div>

                        <!-- Merchant Metadata Badges -->
                        <div class="grid grid-cols-2 gap-2 text-xs py-3 border-y border-slate-100 mb-4 bg-slate-50/60 rounded-lg p-2.5">
                            <div>
                                <span class="text-slate-400 block font-medium">Currency</span>
                                <span class="text-slate-800 font-bold">{{ $merchant->currency }} ({{ $currencySymbol }})</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Timezone</span>
                                <span class="text-slate-800 font-medium truncate block">{{ $merchant->timezone ?? 'UTC' }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Customers</span>
                                <span class="text-slate-800 font-bold">{{ $merchant->customers_count }} accounts</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Active Plan</span>
                                <span class="text-slate-800 font-bold truncate block">
                                    {{ $activePlan?->name ?? 'Default' }}
                                </span>
                            </div>
                        </div>

                        @if($activePlan)
                            <div class="mb-4 text-xs text-slate-600 bg-blue-50/50 border border-blue-100 rounded-lg px-3 py-2 flex items-center justify-between">
                                <span class="font-medium text-blue-900">{{ $activePlan->name }} Plan Quota:</span>
                                <span class="font-bold text-blue-700 font-mono">{{ number_format($activePlan->included_units) }} units</span>
                            </div>
                        @endif

                        <!-- Identifiers Section -->
                        <div class="space-y-1 mb-5">
                            <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Merchant ID</span>
                            <div class="bg-slate-100 rounded px-2.5 py-1 text-xs font-mono text-slate-600 select-all truncate border border-slate-200">
                                {{ $merchant->id }}
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-2">
                        <a href="{{ route('merchants.dashboard', $merchant) }}" class="flex-1 bg-[#1e2532] hover:bg-blue-600 text-white text-xs font-semibold px-4 py-2.5 rounded-lg text-center transition flex items-center justify-center gap-2 shadow-xs group-hover:bg-blue-600">
                            Switch to Dashboard
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </a>
                        <a href="{{ url('/api/v1/merchants/' . $merchant->id . '/dashboard') }}" target="_blank" title="View JSON Analytics API" class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-2.5 rounded-lg font-mono border border-slate-200 transition">
                            {JSON}
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-xl border border-dashed border-slate-300 p-12 text-center">
                    <p class="text-base font-semibold text-slate-700 mb-1">No merchants found in database</p>
                    <p class="text-sm text-slate-500 mb-4">Run the database seeder to populate sample merchant accounts.</p>
                    <code class="bg-slate-100 text-xs px-3 py-1.5 rounded text-slate-800 font-mono">php artisan db:seed</code>
                </div>
            @endforelse
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500 mt-auto">
        <p>Mallow Billing System &copy; {{ date('Y') }} &mdash; High-Throughput Metering & Idempotent Usage Pipeline</p>
    </footer>

</body>
</html>
