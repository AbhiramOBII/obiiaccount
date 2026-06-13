<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\SalesDocument;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public string $tab = 'invoice';
    public string $search = '';
    public string $filterStatus = '';

    public function updatingTab(): void { $this->resetPage(); }
    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFilterStatus(): void { $this->resetPage(); }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'delete'), 403);
        SalesDocument::findOrFail($id)->delete();
    }

    public function convert(int $id, string $toType): void
    {
        abort_unless(auth()->user()->hasPermission('sales', 'create'), 403);
        $source = SalesDocument::with('items')->findOrFail($id);

        $gen = SalesDocument::generateNumber($toType);

        $new = $source->replicate();
        $new->document_type = $toType;
        $new->document_number = $gen['number'];
        $new->document_sequence = $gen['sequence'];
        $new->status = 'draft';
        $new->converted_from_id = $source->id;
        $new->document_date = now();
        $new->due_date = $toType === 'invoice' ? now()->addDays(30) : null;
        $new->save();

        foreach ($source->items as $item) {
            $newItem = $item->replicate();
            $newItem->sales_document_id = $new->id;
            $newItem->save();
        }

        session()->flash('success', ucfirst($toType) . " {$gen['number']} created from {$source->document_number}.");
        $this->tab = $toType;
    }

    public function with(): array
    {
        $query = SalesDocument::where('document_type', $this->tab);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('document_number', 'like', "%{$this->search}%")
                  ->orWhere('customer_name', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Fetch exchange rates for non-INR display
        $exchangeRates = [];
        try {
            $data = Cache::remember('exchange_rates_inr', 3600, function () {
                $response = Http::timeout(5)->get('https://open.er-api.com/v6/latest/INR');
                return $response->successful() ? $response->json() : null;
            });
            if ($data && isset($data['rates'])) {
                foreach ($data['rates'] as $code => $rate) {
                    if ($rate > 0) $exchangeRates[$code] = round(1 / $rate, 2);
                }
            }
        } catch (\Exception $e) {}

        return [
            'documents' => $query->with('user')->latest('document_date')->paginate(15),
            'exchangeRates' => $exchangeRates,
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Sales</h1>
            <p class="mt-1 text-sm text-gray-500">Manage estimates, proforma invoices, and invoices</p>
        </div>
        @if(auth()->user()->hasPermission('sales', 'create'))
            <a href="{{ route('admin.sales.create', ['type' => $tab]) }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                New {{ ucfirst($tab) }}
            </a>
        @endif
    </div>

    {{-- Tabs --}}
    <div class="border-b border-gray-200">
        <nav class="flex gap-0 -mb-px">
            @foreach(['invoice' => 'Invoices', 'proforma' => 'Proforma Invoices', 'estimate' => 'Estimates'] as $key => $label)
                <button
                    wire:click="$set('tab', '{{ $key }}')"
                    class="px-5 py-3 text-sm font-medium border-b-2 transition-colors {{ $tab === $key ? 'border-primary-600 text-primary-700' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search by document number or customer..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                </div>
                <select
                    wire:model.live="filterStatus"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                >
                    <option value="">All Status</option>
                    <option value="draft">Draft</option>
                    <option value="sent">Sent</option>
                    <option value="accepted">Accepted</option>
                    <option value="paid">Paid</option>
                    <option value="declined">Declined</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Number</th>
                        <th class="px-4 py-3 text-left font-medium">Customer</th>
                        <th class="px-4 py-3 text-left font-medium">Date</th>
                        <th class="px-4 py-3 text-right font-medium">Amount</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-left font-medium">Created By</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($documents as $doc)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.sales.edit', $doc) }}" class="font-medium text-primary-700 hover:underline">{{ $doc->document_number }}</a>
                                @if($doc->converted_from_id)
                                    <div class="text-xs text-gray-400">from {{ $doc->convertedFrom?->document_number }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $doc->customer_name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $doc->document_date->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">
                                @php
                                    $sym = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CAD' => 'C$', 'AUD' => 'A$'][$doc->currency] ?? '₹';
                                @endphp
                                {{ $sym }}{{ number_format($doc->grand_total, 2) }}
                                @if($doc->currency !== 'INR' && isset($exchangeRates[$doc->currency]))
                                    <div class="text-xs text-gray-400 font-normal">(₹{{ number_format($doc->grand_total * $exchangeRates[$doc->currency], 2) }})</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-600',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'accepted' => 'bg-green-100 text-green-700',
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'declined' => 'bg-red-100 text-red-700',
                                        'cancelled' => 'bg-yellow-100 text-yellow-700',
                                    ];
                                @endphp
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $statusColors[$doc->status] ?? 'bg-gray-100 text-gray-600' }}">
                                    {{ ucfirst($doc->status) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if($doc->user)
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-primary-100 flex items-center justify-center text-xs font-semibold text-primary-700 shrink-0">
                                            {{ strtoupper(substr($doc->user->name, 0, 1)) }}
                                        </div>
                                        <span class="text-gray-700">{{ $doc->user->name }}</span>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    @if(auth()->user()->hasPermission('sales', 'create'))
                                        @if($doc->document_type === 'estimate')
                                            <button wire:click="convert({{ $doc->id }}, 'proforma')" wire:confirm="Convert to Proforma Invoice?" class="px-2 py-1 text-xs text-blue-600 hover:bg-blue-50 rounded transition-colors" title="Convert to Proforma">→ PI</button>
                                            <button wire:click="convert({{ $doc->id }}, 'invoice')" wire:confirm="Convert to Invoice?" class="px-2 py-1 text-xs text-green-600 hover:bg-green-50 rounded transition-colors" title="Convert to Invoice">→ INV</button>
                                        @elseif($doc->document_type === 'proforma')
                                            <button wire:click="convert({{ $doc->id }}, 'invoice')" wire:confirm="Convert to Invoice?" class="px-2 py-1 text-xs text-green-600 hover:bg-green-50 rounded transition-colors" title="Convert to Invoice">→ INV</button>
                                        @endif
                                    @endif

                                    <a href="{{ route('admin.sales.pdf', $doc) }}" class="p-1.5 text-gray-500 hover:text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="Download PDF" target="_blank">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </a>
                                    @if(auth()->user()->hasPermission('sales', 'update'))
                                        <a href="{{ route('admin.sales.edit', $doc) }}" class="p-1.5 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('sales', 'delete'))
                                        <button wire:click="delete({{ $doc->id }})" wire:confirm="Delete {{ $doc->document_number }}?" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                No {{ $tab === 'proforma' ? 'proforma invoices' : $tab . 's' }} found.
                                <a href="{{ route('admin.sales.create', ['type' => $tab]) }}" class="text-primary-600 hover:underline">Create one</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
