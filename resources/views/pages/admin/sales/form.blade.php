<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Customer;
use App\Models\SalesDocument;
use App\Models\SalesDocumentItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $documentId = null;
    public string $document_type = 'invoice';
    public string $document_number = '';
    public int $document_sequence = 0;

    public ?int $customer_id = null;
    public string $customer_name = '';
    public string $billing_street = '';
    public string $billing_city = '';
    public ?int $billing_state_id = null;
    public string $billing_state_name = '';
    public string $billing_pincode = '';
    public string $billing_country = 'India';
    public string $gstin = '';

    public ?int $place_of_supply_id = null;
    public string $place_of_supply_name = '';

    public string $document_date = '';
    public string $due_date = '';
    public string $tax_type = 'igst';

    public string $notes = '';
    public string $terms = '';
    public string $status = 'draft';
    public string $currency = 'INR';
    public float $exchange_rate = 1.0;

    public array $items = [];

    // Company state (Karnataka = 29)
    public int $companyStateId = 29;

    public function mount(?int $id = null, ?string $type = null): void
    {
        if ($id) {
            $doc = SalesDocument::with('items')->findOrFail($id);
            $this->documentId = $doc->id;
            $this->document_type = $doc->document_type;
            $this->document_number = $doc->document_number;
            $this->document_sequence = $doc->document_sequence;
            $this->customer_id = $doc->customer_id;
            $this->customer_name = $doc->customer_name;
            $this->billing_street = $doc->billing_street ?? '';
            $this->billing_city = $doc->billing_city ?? '';
            $this->billing_state_id = $doc->billing_state_id;
            $this->billing_state_name = $doc->billing_state_name ?? '';
            $this->billing_pincode = $doc->billing_pincode ?? '';
            $this->billing_country = $doc->billing_country ?? 'India';
            $this->gstin = $doc->gstin ?? '';
            $this->place_of_supply_id = $doc->place_of_supply_id;
            $this->place_of_supply_name = $doc->place_of_supply_name ?? '';
            $this->document_date = $doc->document_date->format('Y-m-d');
            $this->due_date = $doc->due_date ? $doc->due_date->format('Y-m-d') : '';
            $this->tax_type = $doc->tax_type;
            $this->notes = $doc->notes ?? '';
            $this->terms = $doc->terms ?? '';
            $this->status = $doc->status;
            $this->currency = $doc->currency;
            $this->exchange_rate = $this->getExchangeRate($this->currency);

            foreach ($doc->items as $item) {
                $this->items[] = [
                    'description' => $item->description,
                    'hsn_sac' => $item->hsn_sac ?? '',
                    'quantity' => (string) $item->quantity,
                    'unit' => $item->unit,
                    'unit_cost' => (string) $item->rate,
                    'tax_percent' => (string) $item->tax_percent,
                ];
            }
        } else {
            $this->document_type = $type ?? 'invoice';
            $gen = SalesDocument::generateNumber($this->document_type);
            $this->document_number = $gen['number'];
            $this->document_sequence = $gen['sequence'];
            $this->document_date = now()->format('Y-m-d');
            if ($this->document_type === 'invoice') {
                $this->due_date = now()->addDays(30)->format('Y-m-d');
            }
            $this->items[] = ['description' => '', 'hsn_sac' => '', 'quantity' => 1, 'unit' => 'Nos', 'unit_cost' => 0, 'tax_percent' => 18];
        }
    }

    public function updatedCustomerId($value): void
    {
        if (!$value) {
            $this->reset(['customer_name', 'billing_street', 'billing_city', 'billing_state_id', 'billing_state_name', 'billing_pincode', 'billing_country', 'gstin', 'place_of_supply_id', 'place_of_supply_name', 'tax_type']);
            $this->currency = 'INR';
            $this->exchange_rate = 1.0;
            return;
        }

        $customer = Customer::find($value);
        if ($customer) {
            $this->customer_name = $customer->name;
            $this->billing_street = $customer->billing_street ?? '';
            $this->billing_city = $customer->billing_city ?? '';
            $this->billing_state_id = $customer->billing_state_id;
            $this->billing_state_name = $customer->billing_state_name ?? '';
            $this->billing_pincode = $customer->billing_pincode ?? '';
            $this->billing_country = $customer->billing_country ?? 'India';
            $this->gstin = $customer->gstin ?? '';
            $this->place_of_supply_id = $customer->billing_state_id;
            $this->place_of_supply_name = $customer->billing_state_name ?? '';
            $this->currency = $customer->currency ?? 'INR';
            $this->exchange_rate = $this->getExchangeRate($this->currency);
            $this->determineTaxType();
        }
    }

    public function updatedPlaceOfSupplyId($value): void
    {
        $states = config('states');
        $this->place_of_supply_name = $value ? ($states[(int) $value] ?? '') : '';
        $this->determineTaxType();
    }

    private function determineTaxType(): void
    {
        $this->tax_type = ($this->place_of_supply_id && (int) $this->place_of_supply_id === $this->companyStateId) ? 'cgst_sgst' : 'igst';
    }

    private function getExchangeRate(string $currency): float
    {
        if ($currency === 'INR') return 1.0;

        try {
            $data = Cache::remember('exchange_rates_inr', 3600, function () {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/INR');
                if ($response->successful()) {
                    return $response->json();
                }
                return null;
            });

            if ($data && isset($data['rates'][$currency]) && $data['rates'][$currency] > 0) {
                return round(1 / $data['rates'][$currency], 2);
            }
        } catch (\Exception $e) {}

        return 1.0;
    }

    private function calculateTotals(): array
    {
        $subtotal = 0;
        $cgst = 0;
        $sgst = 0;
        $igst = 0;
        $computedItems = [];

        foreach ($this->items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $taxPct = (float) ($item['tax_percent'] ?? 0);
            $amount = round($qty * $unitCost, 2);
            $taxAmount = round($amount * $taxPct / 100, 2);

            $itemCgst = 0;
            $itemSgst = 0;
            $itemIgst = 0;

            if ($this->tax_type === 'cgst_sgst') {
                $itemCgst = round($taxAmount / 2, 2);
                $itemSgst = round($taxAmount / 2, 2);
            } else {
                $itemIgst = $taxAmount;
            }

            $subtotal += $amount;
            $cgst += $itemCgst;
            $sgst += $itemSgst;
            $igst += $itemIgst;

            $computedItems[] = array_merge($item, [
                'amount' => $amount,
                'cgst_amount' => $itemCgst,
                'sgst_amount' => $itemSgst,
                'igst_amount' => $itemIgst,
                'total' => $amount + $itemCgst + $itemSgst + $itemIgst,
            ]);
        }

        return [
            'subtotal' => $subtotal,
            'cgst_total' => $cgst,
            'sgst_total' => $sgst,
            'igst_total' => $igst,
            'grand_total' => $subtotal + $cgst + $sgst + $igst,
            'items' => $computedItems,
        ];
    }

    public function save(): void
    {
        $action = $this->documentId ? 'update' : 'create';
        abort_unless(auth()->user()->hasPermission('sales', $action), 403);

        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'document_date' => 'required|date',
            'due_date' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'required|numeric|min:0|max:100',
        ], [
            'items.*.description.required' => 'Description is required.',
            'items.*.quantity.required' => 'Qty is required.',
            'items.*.unit_cost.required' => 'Unit cost is required.',
        ]);

        $calc = $this->calculateTotals();

        $data = [
            'document_type' => $this->document_type,
            'document_number' => $this->document_number,
            'document_sequence' => $this->document_sequence,
            'customer_id' => $this->customer_id,
            'customer_name' => $this->customer_name,
            'billing_street' => $this->billing_street,
            'billing_city' => $this->billing_city,
            'billing_state_id' => $this->billing_state_id,
            'billing_state_name' => $this->billing_state_name,
            'billing_pincode' => $this->billing_pincode,
            'billing_country' => $this->billing_country,
            'gstin' => $this->gstin,
            'place_of_supply_id' => $this->place_of_supply_id,
            'place_of_supply_name' => $this->place_of_supply_name,
            'document_date' => $this->document_date,
            'due_date' => $this->due_date ?: null,
            'tax_type' => $this->tax_type,
            'subtotal' => $calc['subtotal'],
            'cgst_total' => $calc['cgst_total'],
            'sgst_total' => $calc['sgst_total'],
            'igst_total' => $calc['igst_total'],
            'grand_total' => $calc['grand_total'],
            'currency' => $this->currency,
            'notes' => $this->notes,
            'terms' => $this->terms,
            'status' => $this->status,
        ];

        if ($this->documentId) {
            $doc = SalesDocument::findOrFail($this->documentId);
            $doc->update($data);
            $doc->items()->delete();
        } else {
            $data['user_id'] = auth()->id();
            $doc = SalesDocument::create($data);
        }

        foreach ($calc['items'] as $i => $item) {
            $doc->items()->create([
                'sort_order' => $i,
                'description' => $item['description'],
                'hsn_sac' => $item['hsn_sac'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? 'Nos',
                'rate' => $item['unit_cost'],
                'tax_percent' => $item['tax_percent'],
                'amount' => $item['amount'],
                'cgst_amount' => $item['cgst_amount'],
                'sgst_amount' => $item['sgst_amount'],
                'igst_amount' => $item['igst_amount'],
                'total' => $item['total'],
            ]);
        }

        $label = ucfirst($this->document_type);
        session()->flash('success', $this->documentId ? "{$label} updated." : "{$label} {$this->document_number} created.");
        $this->redirect(route('admin.sales.index'), navigate: true);
    }

    public function with(): array
    {
        $customers = Customer::where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return [
            'customers' => $customers,
            'states' => config('states'),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.sales.index') }}" class="p-2 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $documentId ? 'Edit' : 'New' }} {{ ucfirst($document_type) }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $document_number }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Header --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Document # <span class="text-red-500">*</span></label>
                    <input wire:model="document_number" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date <span class="text-red-500">*</span></label>
                    <input wire:model="document_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    @error('document_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                    <input wire:model="due_date" type="date" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select wire:model="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="draft">Draft</option>
                        <option value="sent">Sent</option>
                        <option value="accepted">Accepted</option>
                        <option value="declined">Declined</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Customer --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Customer</h2>
                @if($currency !== 'INR')
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 text-blue-700 text-xs font-semibold rounded-full border border-blue-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Currency: {{ $currency }} (1 {{ $currency }} = ₹{{ number_format($exchange_rate, 2) }})
                    </span>
                @endif
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Select Customer <span class="text-red-500">*</span></label>
                    <select wire:model.live="customer_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Choose customer...</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                    <input wire:model="gstin" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm bg-gray-50 font-mono" readonly />
                </div>
            </div>

            @if($customer_id)
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2 p-3 bg-gray-50 rounded-lg text-sm text-gray-600">
                        <p class="font-medium text-gray-700 mb-1">Billing Address</p>
                        @if($billing_street) <p>{{ $billing_street }}</p> @endif
                        <p>{{ collect([$billing_city, $billing_state_name, $billing_pincode])->filter()->implode(', ') }}</p>
                        <p>{{ $billing_country }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Place of Supply</label>
                        <select wire:model.live="place_of_supply_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                            <option value="">Select...</option>
                            @foreach($states as $sid => $sname)
                                <option value="{{ $sid }}">{{ $sname }} ({{ $sid }})</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Tax: <span class="font-medium {{ $tax_type === 'cgst_sgst' ? 'text-green-600' : 'text-blue-600' }}">
                                {{ $tax_type === 'cgst_sgst' ? 'CGST + SGST' : 'IGST' }}
                            </span>
                        </p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Line Items (Alpine.js driven) --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6"
            x-data="{
                items: @entangle('items'),
                taxType: @entangle('tax_type'),
                currency: @entangle('currency'),
                exchangeRate: @entangle('exchange_rate'),

                currencySymbols: { 'INR': '₹', 'USD': '$', 'EUR': '€', 'GBP': '£', 'CAD': 'C$', 'AUD': 'A$' },

                addItem() {
                    this.items.push({ description: '', hsn_sac: '', quantity: 1, unit: 'Nos', unit_cost: 0, tax_percent: 18 });
                },
                removeItem(i) {
                    if (this.items.length > 1) this.items.splice(i, 1);
                },

                lineRate(item)   { return (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_cost) || 0); },
                lineTax(item)    { return this.lineRate(item) * (parseFloat(item.tax_percent) || 0) / 100; },
                lineAmount(item) { return this.lineRate(item) + this.lineTax(item); },

                get subtotal()   { return this.items.reduce((s, i) => s + this.lineRate(i), 0); },
                get cgstTotal()  { return this.taxType === 'cgst_sgst' ? this.items.reduce((s, i) => s + this.lineTax(i) / 2, 0) : 0; },
                get sgstTotal()  { return this.cgstTotal; },
                get igstTotal()  { return this.taxType === 'igst' ? this.items.reduce((s, i) => s + this.lineTax(i), 0) : 0; },
                get grandTotal() { return this.subtotal + this.cgstTotal + this.sgstTotal + this.igstTotal; },
                get grandTotalInr() { return this.grandTotal * this.exchangeRate; },

                get sym() { return this.currencySymbols[this.currency] || this.currency + ' '; },
                fmt(v) { return this.sym + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
                fmtInr(v) { return '₹' + Number(v).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
            }"
        >
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem()" class="inline-flex items-center gap-1 px-3 py-1.5 text-sm font-medium text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Row
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                        <tr>
                            <th class="px-2 py-2 text-left font-medium w-6">#</th>
                            <th class="px-2 py-2 text-left font-medium min-w-[200px]">Description</th>
                            <th class="px-2 py-2 text-left font-medium w-24">HSN/SAC</th>
                            <th class="px-2 py-2 text-right font-medium w-20">Qty</th>
                            <th class="px-2 py-2 text-right font-medium w-28">Unit Cost</th>
                            <th class="px-2 py-2 text-right font-medium w-28">Rate</th>
                            <th class="px-2 py-2 text-right font-medium w-20">Tax %</th>
                            <th class="px-2 py-2 text-right font-medium w-28">Amount</th>
                            <th class="px-2 py-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-2 py-2 text-gray-400" x-text="index + 1"></td>
                                <td class="px-2 py-2">
                                    <input x-model="item.description" type="text" placeholder="Item description" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                                </td>
                                <td class="px-2 py-2">
                                    <input x-model="item.hsn_sac" type="text" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm font-mono focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                                </td>
                                <td class="px-2 py-2">
                                    <input x-model.number="item.quantity" type="number" step="0.01" min="0" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-right focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                                </td>
                                <td class="px-2 py-2">
                                    <input x-model.number="item.unit_cost" type="number" step="0.01" min="0" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-right focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                                </td>
                                <td class="px-2 py-2 text-right font-medium text-gray-700" x-text="fmt(lineRate(item))"></td>
                                <td class="px-2 py-2">
                                    <input x-model.number="item.tax_percent" type="number" step="0.01" min="0" max="100" class="w-full px-2 py-1.5 border border-gray-300 rounded text-sm text-right focus:ring-1 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                                </td>
                                <td class="px-2 py-2 text-right font-medium text-gray-900" x-text="fmt(lineAmount(item))"></td>
                                <td class="px-2 py-2">
                                    <button x-show="items.length > 1" type="button" @click="removeItem(index)" class="p-1 text-gray-400 hover:text-red-500 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="mt-6 flex justify-end">
                <div class="w-full max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span class="font-medium" x-text="fmt(subtotal)"></span>
                    </div>
                    <template x-if="taxType === 'cgst_sgst'">
                        <div class="space-y-2">
                            <div class="flex justify-between text-gray-600">
                                <span>CGST</span>
                                <span x-text="fmt(cgstTotal)"></span>
                            </div>
                            <div class="flex justify-between text-gray-600">
                                <span>SGST</span>
                                <span x-text="fmt(sgstTotal)"></span>
                            </div>
                        </div>
                    </template>
                    <template x-if="taxType === 'igst'">
                        <div class="flex justify-between text-gray-600">
                            <span>IGST</span>
                            <span x-text="fmt(igstTotal)"></span>
                        </div>
                    </template>
                    <div class="flex justify-between pt-2 border-t border-gray-200 text-base font-bold text-gray-900">
                        <span>Grand Total</span>
                        <span x-text="fmt(grandTotal)"></span>
                    </div>
                    <template x-if="currency !== 'INR'">
                        <div class="flex justify-between pt-2 border-t border-gray-200 text-sm">
                            <span class="text-gray-500">INR Equivalent <span class="text-xs">(1 <span x-text="currency"></span> = <span x-text="fmtInr(exchangeRate)"></span>)</span></span>
                            <span class="font-bold text-green-700" x-text="fmtInr(grandTotalInr)"></span>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Notes & Terms --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea wire:model="notes" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="Any notes for the customer..."></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                    <textarea wire:model="terms" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="Payment terms, conditions..."></textarea>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.sales.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            @if($documentId)
                <a href="{{ route('admin.sales.pdf', $documentId) }}" target="_blank" class="inline-flex items-center gap-2 px-5 py-2.5 border border-purple-300 text-purple-700 text-sm font-medium rounded-lg hover:bg-purple-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Download PDF
                </a>
            @endif
            <button
                type="submit"
                class="px-6 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
            >
                <span wire:loading.remove>{{ $documentId ? 'Update' : 'Save' }} {{ ucfirst($document_type) }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
