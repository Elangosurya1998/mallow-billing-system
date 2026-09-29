<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Merchant Dashboard — {{ $merchant_name }}</title>
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <a href="{{ route('merchants.index') }}" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 text-xs font-semibold bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded border border-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    All Merchants
                </a>
                <h1 class="text-white text-lg font-bold tracking-tight">
                    Merchant Dashboard — <span class="font-normal">{{ $merchant_name }}</span>
                </h1>
            </div>

            <div class="flex items-center gap-3">
                @if(isset($current_merchant))
                    <a href="{{ route('merchants.customers.create', $current_merchant) }}" class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-3 py-1.5 rounded transition flex items-center gap-1.5 shadow-sm">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        + Add Customer
                    </a>
                    <a href="{{ route('merchants.edit', $current_merchant) }}" class="bg-slate-700 hover:bg-slate-600 text-slate-200 hover:text-white text-xs font-semibold px-3 py-1.5 rounded transition flex items-center gap-1.5 border border-slate-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        Edit Merchant
                    </a>
                @endif

                <!-- Quick Switcher Dropdown -->
                @if(isset($all_merchants) && $all_merchants->count() > 0)
                    <div class="flex items-center gap-2 bg-slate-800/90 border border-slate-600 rounded px-2.5 py-1 text-xs">
                        <label for="merchant-quick-switch" class="text-slate-400 font-medium whitespace-nowrap">Switch Merchant:</label>
                        <select id="merchant-quick-switch" onchange="if(this.value) window.location.href=this.value;" class="bg-transparent text-white font-semibold focus:outline-none cursor-pointer pr-1">
                            @foreach($all_merchants as $m)
                                <option value="{{ route('merchants.dashboard', $m) }}" {{ ((isset($current_merchant) && $m->id === $current_merchant->id) || $m->name === $merchant_name) ? 'selected' : '' }} class="bg-slate-900 text-white">
                                    {{ $m->name }} ({{ $m->currency }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <a href="{{ isset($current_merchant) ? route('merchants.console', $current_merchant) : route('api.console') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3 py-1.5 rounded transition flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    API Console
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-7xl mx-auto px-6 py-8 space-y-6 flex-1 w-full">

        <!-- Flash Success Notification -->
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-xl text-xs flex items-center justify-between shadow-xs">
                <div class="flex items-center gap-2">
                    <span class="text-emerald-600 text-base">✅</span>
                    <span class="font-medium">{{ session('success') }}</span>
                </div>
                <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 font-bold">&times;</button>
            </div>
        @endif

        <!-- Top Row Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

            <!-- Cycle Usage Card -->
            <div class="bg-white rounded-lg border border-slate-200 border-l-4 border-l-[#2b6cb0] p-5 shadow-xs">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    Current Cycle Usage vs. Plan Quota
                </p>
                <p class="text-2xl font-bold text-slate-900 tracking-tight font-mono">
                    {{ $cycle_usage_display }}
                </p>
            </div>

            <!-- Projected Overage Revenue Card -->
            <div class="bg-white rounded-lg border border-slate-200 border-l-4 border-l-amber-500 p-5 shadow-xs">
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">
                    Projected Overage Revenue
                </p>
                <p class="text-2xl font-bold text-slate-900 tracking-tight">
                    {{ $projected_overage_revenue }}
                </p>
            </div>

            <!-- Active Plan Card -->
            <div class="bg-white rounded-lg border border-slate-200 border-l-4 border-l-emerald-600 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                        Active Plan
                    </p>
                    @if(isset($current_merchant))
                        <span class="text-[11px] font-bold text-blue-700 bg-blue-50 px-2 py-0.5 rounded">
                            {{ $current_merchant->customers()->count() }} Customers
                        </span>
                    @endif
                </div>
                <p class="text-2xl font-bold text-slate-900 tracking-tight truncate">
                    {{ $active_plan }}
                </p>
            </div>

        </div>

        <!-- 2-Column Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Left Column: Top Customers & Usage Trend -->
            <div class="lg:col-span-8 space-y-6">

                <!-- Top 5 Customers Table -->
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-sm font-bold text-slate-800">
                            Top 5 Customers by Usage (this cycle)
                        </h2>
                        @if(isset($current_merchant))
                            <a href="{{ route('merchants.customers.create', $current_merchant) }}" class="text-xs text-blue-600 hover:text-blue-800 font-semibold flex items-center gap-1 hover:underline">
                                + Add Customer
                            </a>
                        @endif
                    </div>

                    <div class="border border-slate-200 rounded-md overflow-hidden">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-[#edf2f7] border-b border-slate-200 text-xs font-semibold text-slate-700">
                                <tr>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3 text-right">Usage</th>
                                    <th class="px-4 py-3 text-right">% of Allowance</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse ($top_customers as $customer)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-800">
                                            {{ $customer['name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-600 font-mono">
                                            {{ $customer['usage_formatted'] }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-800 font-semibold">
                                            {{ $customer['percent_of_allowance_formatted'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="px-4 py-4 text-center text-xs text-slate-400 italic">
                                            No usage events recorded for this cycle yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <p class="text-xs text-slate-400 mt-2 italic">
                        .... (top 5, sorted desc.)
                    </p>
                </div>

                <!-- Daily Usage Trend Line Chart -->
                <div class="bg-white rounded-lg border border-slate-200 p-6 shadow-xs">
                    <h2 class="text-sm font-bold text-slate-800 mb-1">
                        Daily Usage Trend (last 30 days)
                    </h2>
                    <p class="text-xs text-slate-400 font-mono mb-4">
                        usage / day
                    </p>

                    <div class="h-56 w-full">
                        <canvas id="dailyUsageChart"></canvas>
                    </div>
                </div>

            </div>

            <!-- Right Column: Churn Risk & System Status -->
            <div class="lg:col-span-4 space-y-6">

                <!-- Churn Risk Card -->
                <div class="bg-white rounded-lg border-2 border-red-300 p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-red-600 mb-3 flex items-center gap-1.5">
                        <span class="text-red-500 font-black">▲</span> Churn Risk (usage ↓ >50% MoM)
                    </h2>
                    <ul class="space-y-2 text-sm text-slate-700">
                        @forelse ($churn_alerts as $alert)
                            <li class="flex items-start gap-2">
                                <span class="text-red-500 font-bold">&bull;</span>
                                <span>{{ $alert['text'] }}</span>
                            </li>
                        @empty
                            <li class="text-xs text-slate-400 italic">
                                No customer accounts flagged with &gt;50% usage drop.
                            </li>
                        @endforelse
                    </ul>
                </div>

                <!-- System Status Card -->
                <div class="bg-sky-50/40 rounded-lg border-2 border-sky-300 p-5 shadow-xs">
                    <h2 class="text-sm font-bold text-sky-800 mb-3">
                        System status (informational)
                    </h2>
                    <ul class="space-y-2.5 text-xs text-slate-700 leading-relaxed">
                        <li class="flex items-start gap-2">
                            <span class="text-sky-600 font-bold">&bull;</span>
                            <span>Plan pricing cache: <strong class="font-semibold text-slate-800">Redis, TTL 10m</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-sky-600 font-bold">&bull;</span>
                            <span>Nightly aggregation job: <strong class="font-semibold text-slate-800">queued, chunked (5k rows/batch)</strong></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-sky-600 font-bold">&bull;</span>
                            <span>Usage endpoint: <strong class="font-semibold text-slate-800">rate-limited 120 req/min per API key</strong></span>
                        </li>
                    </ul>
                </div>

            </div>

        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500 mt-auto">
        <p>Mallow Billing System &copy; {{ date('Y') }} &mdash; High-Throughput Metering & Idempotent Usage Pipeline</p>
    </footer>

    <!-- Chart.js Configuration -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const ctx = document.getElementById('dailyUsageChart').getContext('2d');
            const labels = @json($trend_labels);
            const dataValues = @json($trend_values);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Units',
                        data: dataValues,
                        borderColor: '#2b6cb0',
                        backgroundColor: 'rgba(43, 108, 176, 0.04)',
                        borderWidth: 2,
                        tension: 0.35,
                        fill: true,
                        pointRadius: 0,
                        pointHoverRadius: 4,
                        pointHoverBackgroundColor: '#2b6cb0'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.y.toLocaleString() + ' units';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                color: '#e2e8f0',
                                drawTicks: false
                            },
                            ticks: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: '#f1f5f9'
                            },
                            ticks: {
                                font: { size: 10 },
                                color: '#94a3b8',
                                callback: function(value) {
                                    return value >= 1000 ? (value / 1000) + 'k' : value;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>
