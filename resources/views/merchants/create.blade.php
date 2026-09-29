<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Merchant — Mallow Billing System</title>
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
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('merchants.index') }}" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 text-xs font-semibold bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded border border-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    All Merchants
                </a>
                <div>
                    <h1 class="text-white text-base font-bold tracking-tight leading-tight">
                        Register New Merchant
                    </h1>
                    <p class="text-slate-400 text-xs font-medium">
                        Create an isolated multi-tenant billing workspace
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('api.console') }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    API Console
                </a>
            </div>
        </div>
    </header>

    <!-- Main Container -->
    <main class="max-w-4xl mx-auto px-6 py-8 flex-1 w-full space-y-6">

        @if($errors->any())
            <div class="bg-rose-50 border border-rose-200 text-rose-800 px-4 py-3 rounded-xl text-xs space-y-1">
                <p class="font-bold flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    Please fix the following validation errors:
                </p>
                <ul class="list-disc list-inside space-y-0.5 pl-2 text-rose-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white rounded-xl border border-slate-200 p-8 shadow-xs">
            <div class="border-b border-slate-100 pb-5 mb-6">
                <h2 class="text-xl font-bold text-slate-900 tracking-tight">Merchant Details</h2>
                <p class="text-xs text-slate-500 mt-1">
                    Each merchant operates with strict multi-tenant database partitioning, currency isolation, and independent billing quotas.
                </p>
            </div>

            <form action="{{ route('merchants.store') }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Merchant Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Merchant Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Apex Cloud Labs" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                        <p class="text-[11px] text-slate-400 mt-1">Official trading name or company organization.</p>
                    </div>

                    <!-- Unique Slug -->
                    <div>
                        <label for="slug" class="block text-xs font-semibold text-slate-700 mb-1">
                            Identifier Slug (Optional)
                        </label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug') }}" placeholder="e.g. apex-cloud-labs (auto-generated if empty)" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 font-mono transition">
                        <p class="text-[11px] text-slate-400 mt-1">URL-safe tenant key. Auto-generated from name if left blank.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Billing Contact Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                            Billing Contact Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="finance@apexcloud.io" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                        <p class="text-[11px] text-slate-400 mt-1">Where billing cycle alerts and invoices will be directed.</p>
                    </div>

                    <!-- Operational Currency -->
                    <div>
                        <label for="currency" class="block text-xs font-semibold text-slate-700 mb-1">
                            Primary Currency <span class="text-rose-500">*</span>
                        </label>
                        <select name="currency" id="currency" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition font-mono">
                            <option value="USD" {{ old('currency', 'USD') === 'USD' ? 'selected' : '' }}>USD — US Dollar ($)</option>
                            <option value="EUR" {{ old('currency') === 'EUR' ? 'selected' : '' }}>EUR — Euro (€)</option>
                            <option value="GBP" {{ old('currency') === 'GBP' ? 'selected' : '' }}>GBP — British Pound (£)</option>
                            <option value="INR" {{ old('currency') === 'INR' ? 'selected' : '' }}>INR — Indian Rupee (₹)</option>
                            <option value="CAD" {{ old('currency') === 'CAD' ? 'selected' : '' }}>CAD — Canadian Dollar ($)</option>
                            <option value="AUD" {{ old('currency') === 'AUD' ? 'selected' : '' }}>AUD — Australian Dollar ($)</option>
                            <option value="SGD" {{ old('currency') === 'SGD' ? 'selected' : '' }}>SGD — Singapore Dollar ($)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">All amounts will be stored in integer cents of this currency.</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Default Timezone -->
                    <div>
                        <label for="timezone" class="block text-xs font-semibold text-slate-700 mb-1">
                            Billing Timezone <span class="text-rose-500">*</span>
                        </label>
                        <select name="timezone" id="timezone" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition font-mono">
                            <option value="UTC" {{ old('timezone', 'UTC') === 'UTC' ? 'selected' : '' }}>UTC (Coordinated Universal Time)</option>
                            <option value="America/New_York" {{ old('timezone') === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST/EDT)</option>
                            <option value="America/Los_Angeles" {{ old('timezone') === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (PST/PDT)</option>
                            <option value="Europe/London" {{ old('timezone') === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT/BST)</option>
                            <option value="Europe/Berlin" {{ old('timezone') === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin (CET/CEST)</option>
                            <option value="Asia/Kolkata" {{ old('timezone') === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                            <option value="Asia/Singapore" {{ old('timezone') === 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (SGT)</option>
                            <option value="Asia/Tokyo" {{ old('timezone') === 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (JST)</option>
                        </select>
                        <p class="text-[11px] text-slate-400 mt-1">Calendar day boundaries for daily usage rollups.</p>
                    </div>

                    <!-- Initial Status -->
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700 mb-1">
                            Account Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" id="status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active (Ready for ingestion & billing)</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive (Staging / Onboarding)</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>

                <div class="bg-blue-50/60 border border-blue-100 rounded-xl p-4 text-xs text-slate-600 flex items-start gap-3">
                    <span class="text-blue-600 text-lg">💡</span>
                    <div>
                        <span class="font-bold text-blue-900 block">Automatic Provisioning</span>
                        When created, default <strong class="text-slate-800">Starter Plan</strong> (10,000 units quota, $29/mo) and <strong class="text-slate-800">Growth Plan</strong> (50,000 units quota, $79/mo) will be initialized automatically so customers can be enrolled right away.
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('merchants.index') }}" class="text-xs font-medium text-slate-600 hover:text-slate-900 px-4 py-2.5 rounded-lg border border-slate-200 hover:bg-slate-100 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-6 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        <span>Create Merchant</span>
                    </button>
                </div>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500 mt-auto">
        <p>Mallow Billing System &copy; {{ date('Y') }} &mdash; Multi-Tenant Account Lifecycle</p>
    </footer>

</body>
</html>
