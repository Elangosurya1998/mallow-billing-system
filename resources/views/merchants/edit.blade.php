<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Merchant: {{ $merchant->name }} — Mallow Billing System</title>
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
                <a href="{{ route('merchants.dashboard', $merchant) }}" class="text-slate-300 hover:text-white transition flex items-center gap-1.5 text-xs font-semibold bg-slate-800 hover:bg-slate-700 px-3 py-1.5 rounded border border-slate-600">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Back to Dashboard
                </a>
                <div>
                    <h1 class="text-white text-base font-bold tracking-tight leading-tight">
                        Edit Merchant: <span class="font-normal">{{ $merchant->name }}</span>
                    </h1>
                    <p class="text-slate-400 text-xs font-medium">
                        Update configuration, operational currency, and timezone
                    </p>
                </div>
            </div>
            <div>
                <a href="{{ route('merchants.console', $merchant) }}" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg shadow-sm transition flex items-center gap-1.5">
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
            <div class="border-b border-slate-100 pb-5 mb-6 flex items-start justify-between">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Merchant Settings</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Editing properties for <strong class="text-slate-700 font-mono">{{ $merchant->id }}</strong>
                    </p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 font-mono">
                    ID: {{ substr($merchant->id, 0, 8) }}...
                </span>
            </div>

            <form action="{{ route('merchants.update', $merchant) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Merchant Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Merchant Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name', $merchant->name) }}" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <!-- Unique Slug -->
                    <div>
                        <label for="slug" class="block text-xs font-semibold text-slate-700 mb-1">
                            Identifier Slug <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="slug" id="slug" value="{{ old('slug', $merchant->slug) }}" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 font-mono transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Billing Contact Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                            Billing Contact Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email', $merchant->email) }}" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <!-- Operational Currency -->
                    <div>
                        <label for="currency" class="block text-xs font-semibold text-slate-700 mb-1">
                            Primary Currency <span class="text-rose-500">*</span>
                        </label>
                        <select name="currency" id="currency" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition font-mono">
                            <option value="USD" {{ old('currency', $merchant->currency) === 'USD' ? 'selected' : '' }}>USD — US Dollar ($)</option>
                            <option value="EUR" {{ old('currency', $merchant->currency) === 'EUR' ? 'selected' : '' }}>EUR — Euro (€)</option>
                            <option value="GBP" {{ old('currency', $merchant->currency) === 'GBP' ? 'selected' : '' }}>GBP — British Pound (£)</option>
                            <option value="INR" {{ old('currency', $merchant->currency) === 'INR' ? 'selected' : '' }}>INR — Indian Rupee (₹)</option>
                            <option value="CAD" {{ old('currency', $merchant->currency) === 'CAD' ? 'selected' : '' }}>CAD — Canadian Dollar ($)</option>
                            <option value="AUD" {{ old('currency', $merchant->currency) === 'AUD' ? 'selected' : '' }}>AUD — Australian Dollar ($)</option>
                            <option value="SGD" {{ old('currency', $merchant->currency) === 'SGD' ? 'selected' : '' }}>SGD — Singapore Dollar ($)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Default Timezone -->
                    <div>
                        <label for="timezone" class="block text-xs font-semibold text-slate-700 mb-1">
                            Billing Timezone <span class="text-rose-500">*</span>
                        </label>
                        <select name="timezone" id="timezone" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition font-mono">
                            <option value="UTC" {{ old('timezone', $merchant->timezone) === 'UTC' ? 'selected' : '' }}>UTC (Coordinated Universal Time)</option>
                            <option value="America/New_York" {{ old('timezone', $merchant->timezone) === 'America/New_York' ? 'selected' : '' }}>America/New_York (EST/EDT)</option>
                            <option value="America/Los_Angeles" {{ old('timezone', $merchant->timezone) === 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (PST/PDT)</option>
                            <option value="Europe/London" {{ old('timezone', $merchant->timezone) === 'Europe/London' ? 'selected' : '' }}>Europe/London (GMT/BST)</option>
                            <option value="Europe/Berlin" {{ old('timezone', $merchant->timezone) === 'Europe/Berlin' ? 'selected' : '' }}>Europe/Berlin (CET/CEST)</option>
                            <option value="Asia/Kolkata" {{ old('timezone', $merchant->timezone) === 'Asia/Kolkata' ? 'selected' : '' }}>Asia/Kolkata (IST)</option>
                            <option value="Asia/Singapore" {{ old('timezone', $merchant->timezone) === 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (SGT)</option>
                            <option value="Asia/Tokyo" {{ old('timezone', $merchant->timezone) === 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (JST)</option>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-xs font-semibold text-slate-700 mb-1">
                            Account Status <span class="text-rose-500">*</span>
                        </label>
                        <select name="status" id="status" required class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                            <option value="active" {{ old('status', $merchant->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $merchant->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status', $merchant->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                </div>

                <!-- Form Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('merchants.dashboard', $merchant) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900 px-4 py-2.5 rounded-lg border border-slate-200 hover:bg-slate-100 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-6 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        <span>Save Changes</span>
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
