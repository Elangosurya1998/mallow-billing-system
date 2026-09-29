<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer — {{ $merchant->name }}</title>
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
                        Add Customer to {{ $merchant->name }}
                    </h1>
                    <p class="text-slate-400 text-xs font-medium">
                        Enroll a new client account under this merchant with quota and plan assignment
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
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">New Customer Profile</h2>
                    <p class="text-xs text-slate-500 mt-1">
                        Merchant Tenant: <strong class="text-slate-800">{{ $merchant->name }}</strong> (Currency: {{ $merchant->currency }})
                    </p>
                </div>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                    Tenant-Scoped
                </span>
            </div>

            <form action="{{ route('merchants.customers.store', $merchant) }}" method="POST" class="space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer Name -->
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                            Customer / Company Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="e.g. Acme Labs International" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                    </div>

                    <!-- Customer Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                            Customer Email <span class="text-rose-500">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required placeholder="billing@acmelabs.com" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Currency -->
                    <div>
                        <label for="currency" class="block text-xs font-semibold text-slate-700 mb-1">
                            Currency
                        </label>
                        <input type="text" name="currency" id="currency" value="{{ old('currency', $merchant->currency) }}" readonly class="w-full bg-slate-100 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-600 font-mono cursor-not-allowed">
                        <p class="text-[11px] text-slate-400 mt-1">Inherited from parent merchant.</p>
                    </div>

                    <!-- Initial Credit Balance -->
                    <div>
                        <label for="credit_balance" class="block text-xs font-semibold text-slate-700 mb-1">
                            Initial Credit Balance ({{ $merchant->currency }})
                        </label>
                        <input type="number" step="0.01" min="0" name="credit_balance" id="credit_balance" value="{{ old('credit_balance', '0.00') }}" placeholder="0.00" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 font-mono transition">
                        <p class="text-[11px] text-slate-400 mt-1">Applied against future invoices.</p>
                    </div>

                    <!-- External Reference / ERP ID -->
                    <div>
                        <label for="external_reference" class="block text-xs font-semibold text-slate-700 mb-1">
                            External Reference (Optional)
                        </label>
                        <input type="text" name="external_reference" id="external_reference" value="{{ old('external_reference') }}" placeholder="e.g. ERP-10492" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 font-mono transition">
                    </div>
                </div>

                <!-- Initial Subscription Plan Assignment -->
                <div>
                    <label for="plan_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Initial Subscription Plan
                    </label>
                    <select name="plan_id" id="plan_id" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-3.5 py-2.5 text-xs text-slate-900 focus:bg-white focus:outline-none focus:border-blue-500 transition">
                        <option value="">-- No initial subscription (Add later) --</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ old('plan_id') === $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} &mdash; {{ number_format($plan->base_price_cents / 100, 2) }} {{ $merchant->currency }}/mo (Includes {{ number_format($plan->included_units) }} units, {{ $plan->overage_unit_price_cents }}¢ overage)
                            </option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Selecting a plan will immediately activate a billing cycle period for this customer so they can ingest events and generate invoices.
                    </p>
                </div>

                <!-- Form Buttons -->
                <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                    <a href="{{ route('merchants.dashboard', $merchant) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900 px-4 py-2.5 rounded-lg border border-slate-200 hover:bg-slate-100 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-6 py-2.5 rounded-lg shadow-sm transition flex items-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        <span>Add Customer & Activate</span>
                    </button>
                </div>
            </form>
        </div>

    </main>

    <!-- Footer -->
    <footer class="border-t border-slate-200 bg-white py-4 px-6 text-center text-xs text-slate-500 mt-auto">
        <p>Mallow Billing System &copy; {{ date('Y') }} &mdash; Customer Account Enrollment</p>
    </footer>

</body>
</html>
