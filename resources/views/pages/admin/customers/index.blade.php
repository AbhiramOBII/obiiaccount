<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;
use App\Models\Customer;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterStatus = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('customers', 'update'), 403);
        $customer = Customer::findOrFail($id);
        $customer->update(['is_active' => !$customer->is_active]);
    }

    public function delete(int $id): void
    {
        abort_unless(auth()->user()->hasPermission('customers', 'delete'), 403);
        Customer::findOrFail($id)->delete();
    }

    public function with(): array
    {
        $query = Customer::query();

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%")
                  ->orWhere('phone', 'like', "%{$this->search}%")
                  ->orWhere('mobile', 'like', "%{$this->search}%")
                  ->orWhere('gstin', 'like', "%{$this->search}%");
            });
        }

        if ($this->filterStatus !== '') {
            $query->where('is_active', $this->filterStatus === '1');
        }

        return [
            'customers' => $query->latest()->paginate(15),
        ];
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Customers</h1>
            <p class="mt-1 text-sm text-gray-500">Manage your customer database</p>
        </div>
        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission('customers', 'create'))
                <a href="{{ route('admin.customers.import') }}" class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </a>
                <a href="{{ route('admin.customers.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Add Customer
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input
                        wire:model.live.debounce.300ms="search"
                        type="text"
                        placeholder="Search by name, email, phone, GSTIN..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                    />
                </div>
                <select
                    wire:model.live="filterStatus"
                    class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none"
                >
                    <option value="">All Status</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium">Name</th>
                        <th class="px-4 py-3 text-left font-medium">Contact</th>
                        <th class="px-4 py-3 text-left font-medium">GSTIN</th>
                        <th class="px-4 py-3 text-left font-medium">City</th>
                        <th class="px-4 py-3 text-center font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($customers as $customer)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $customer->name }}</div>
                                @if($customer->contact_name)
                                    <div class="text-xs text-gray-500">{{ $customer->contact_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                <div>{{ $customer->email ?? '—' }}</div>
                                <div class="text-xs">{{ $customer->mobile ?? $customer->phone ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $customer->gstin ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $customer->billing_city ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleActive({{ $customer->id }})" wire:confirm="Toggle active status for {{ $customer->name }}?">
                                    @if($customer->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactive</span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if(auth()->user()->hasPermission('customers', 'update'))
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="p-1.5 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </a>
                                    @endif
                                    @if(auth()->user()->hasPermission('customers', 'delete'))
                                        <button wire:click="delete({{ $customer->id }})" wire:confirm="Are you sure you want to delete {{ $customer->name }}?" class="p-1.5 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                                <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                No customers found. <a href="{{ route('admin.customers.create') }}" class="text-primary-600 hover:underline">Add your first customer</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="p-4 border-t border-gray-200">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
