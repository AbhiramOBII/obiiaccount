<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

new #[Layout('components.layouts.admin')] class extends Component
{
    public array $rates = [];
    public ?string $ratesDate = null;
    public ?string $ratesError = null;

    public function mount(): void
    {
        $this->fetchRates();
    }

    private function fetchRates(): void
    {
        try {
            $data = Cache::remember('exchange_rates_inr', 3600, function () {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/INR');
                if ($response->successful()) {
                    return $response->json();
                }
                return null;
            });

            if ($data && isset($data['rates'])) {
                $inrRates = $data['rates'];
                // We need "X to INR" — the API gives INR-based rates, so 1 INR = X foreign
                // Invert: 1 foreign = 1 / rate INR
                $currencies = ['USD', 'EUR', 'CAD', 'AUD', 'GBP'];
                foreach ($currencies as $code) {
                    if (isset($inrRates[$code]) && $inrRates[$code] > 0) {
                        $this->rates[$code] = round(1 / $inrRates[$code], 2);
                    }
                }
                $this->ratesDate = $data['time_last_update_utc'] ?? now()->format('d M Y');
            } else {
                $this->ratesError = 'Unable to fetch rates.';
            }
        } catch (\Exception $e) {
            $this->ratesError = 'Unable to fetch rates.';
        }
    }
};
?>

<div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back, {{ auth()->user()->name }}.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Invoices</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">0</p>
                    </div>
                    <div class="p-3 bg-primary-50 rounded-lg">
                        <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Pending Amount</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">₹0</p>
                    </div>
                    <div class="p-3 bg-yellow-50 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Paid Amount</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">₹0</p>
                    </div>
                    <div class="p-3 bg-green-50 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Exchange Rates --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Today's Exchange Rates</h2>
                @if($ratesDate)
                    <span class="text-xs text-gray-400">Updated: {{ \Carbon\Carbon::parse($ratesDate)->format('d M Y, h:i A') }}</span>
                @endif
            </div>

            @if($ratesError)
                <p class="text-sm text-red-500">{{ $ratesError }}</p>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                    @php
                        $currencyLabels = [
                            'USD' => ['name' => 'US Dollar', 'symbol' => '$', 'flag' => '🇺🇸'],
                            'EUR' => ['name' => 'Euro', 'symbol' => '€', 'flag' => '🇪🇺'],
                            'GBP' => ['name' => 'British Pound', 'symbol' => '£', 'flag' => '🇬🇧'],
                            'CAD' => ['name' => 'Canadian Dollar', 'symbol' => 'C$', 'flag' => '🇨🇦'],
                            'AUD' => ['name' => 'Australian Dollar', 'symbol' => 'A$', 'flag' => '🇦🇺'],
                        ];
                    @endphp

                    @foreach($currencyLabels as $code => $info)
                        @if(isset($rates[$code]))
                            <div class="bg-gray-50 rounded-lg p-4 text-center border border-gray-100">
                                <div class="text-2xl mb-1">{{ $info['flag'] }}</div>
                                <div class="text-xs font-medium text-gray-500 mb-1">{{ $info['symbol'] }}1 {{ $code }}</div>
                                <div class="text-lg font-bold text-gray-900">₹{{ number_format($rates[$code], 2) }}</div>
                            </div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
</div>
