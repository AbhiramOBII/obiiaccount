<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Customer;

new #[Layout('components.layouts.admin')] class extends Component
{
    public ?int $customerId = null;

    public string $name = '';
    public string $contact_name = '';
    public string $phone = '';
    public string $mobile = '';
    public string $email = '';

    public string $gstin = '';
    public string $gst_type = 'unregistered';
    public string $pan = '';
    public bool $is_active = true;

    public string $billing_street = '';
    public string $billing_city = '';
    public ?int $billing_state_id = null;
    public string $billing_state_name = '';
    public string $billing_pincode = '';
    public string $billing_country = 'India';

    public string $shipping_street = '';
    public string $shipping_city = '';
    public ?int $shipping_state_id = null;
    public string $shipping_state_name = '';
    public string $shipping_pincode = '';
    public string $shipping_country = 'India';

    public string $credit_limit = '0.00';
    public string $currency = 'INR';

    public bool $copyBilling = false;

    public function mount(?int $id = null): void
    {
        if ($id) {
            $customer = Customer::findOrFail($id);
            $this->customerId = $customer->id;
            $this->name = $customer->name ?? '';
            $this->contact_name = $customer->contact_name ?? '';
            $this->phone = $customer->phone ?? '';
            $this->mobile = $customer->mobile ?? '';
            $this->email = $customer->email ?? '';
            $this->gstin = $customer->gstin ?? '';
            $this->gst_type = $customer->gst_type ?? 'unregistered';
            $this->pan = $customer->pan ?? '';
            $this->is_active = (bool) $customer->is_active;
            $this->billing_street = $customer->billing_street ?? '';
            $this->billing_city = $customer->billing_city ?? '';
            $this->billing_state_id = $customer->billing_state_id;
            $this->billing_state_name = $customer->billing_state_name ?? '';
            $this->billing_pincode = $customer->billing_pincode ?? '';
            $this->billing_country = $customer->billing_country ?? 'India';
            $this->shipping_street = $customer->shipping_street ?? '';
            $this->shipping_city = $customer->shipping_city ?? '';
            $this->shipping_state_id = $customer->shipping_state_id;
            $this->shipping_state_name = $customer->shipping_state_name ?? '';
            $this->shipping_pincode = $customer->shipping_pincode ?? '';
            $this->shipping_country = $customer->shipping_country ?? 'India';
            $this->credit_limit = (string) ($customer->credit_limit ?? '0.00');
            $this->currency = $customer->currency ?? 'INR';
        }
    }

    public function updatedBillingStateId($value): void
    {
        $this->billing_state_name = $value ? (config('states')[(int) $value] ?? '') : '';
    }

    public function updatedShippingStateId($value): void
    {
        $this->shipping_state_name = $value ? (config('states')[(int) $value] ?? '') : '';
    }

    public function updatedCopyBilling(bool $value): void
    {
        if ($value) {
            $this->shipping_street = $this->billing_street;
            $this->shipping_city = $this->billing_city;
            $this->shipping_state_id = $this->billing_state_id;
            $this->shipping_state_name = $this->billing_state_name;
            $this->shipping_pincode = $this->billing_pincode;
            $this->shipping_country = $this->billing_country;
        }
    }

    public function save(): void
    {
        $action = $this->customerId ? 'update' : 'create';
        abort_unless(auth()->user()->hasPermission('customers', $action), 403);

        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'mobile' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'gstin' => 'nullable|string|max:15',
            'gst_type' => 'required|in:regular,unregistered,consumer,overseas',
            'pan' => 'nullable|string|max:10',
            'is_active' => 'boolean',
            'billing_street' => 'nullable|string|max:255',
            'billing_city' => 'nullable|string|max:100',
            'billing_state_id' => 'nullable|integer',
            'billing_state_name' => 'nullable|string|max:100',
            'billing_pincode' => 'nullable|string|max:10',
            'billing_country' => 'nullable|string|max:100',
            'shipping_street' => 'nullable|string|max:255',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_state_id' => 'nullable|integer',
            'shipping_state_name' => 'nullable|string|max:100',
            'shipping_pincode' => 'nullable|string|max:10',
            'shipping_country' => 'nullable|string|max:100',
            'credit_limit' => 'nullable|numeric|min:0',
            'currency' => 'required|string|max:10',
        ]);

        if ($this->customerId) {
            Customer::findOrFail($this->customerId)->update($validated);
            session()->flash('success', 'Customer updated successfully.');
        } else {
            Customer::create($validated);
            session()->flash('success', 'Customer created successfully.');
        }

        $this->redirect(route('admin.customers.index'), navigate: true);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.customers.index') }}" class="p-2 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $customerId ? 'Edit Customer' : 'New Customer' }}</h1>
            <p class="mt-1 text-sm text-gray-500">{{ $customerId ? 'Update customer details' : 'Add a new customer to your database' }}</p>
        </div>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{-- Basic Information --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Name <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact Person</label>
                    <input wire:model="contact_name" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input wire:model="email" type="email" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input wire:model="phone" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mobile</label>
                    <input wire:model="mobile" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="flex items-center gap-2 mt-6">
                        <input wire:model="is_active" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                        <span class="text-sm font-medium text-gray-700">Active Customer</span>
                    </label>
                </div>
            </div>
        </div>

        {{-- Tax Information --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Tax Information</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GSTIN</label>
                    <input wire:model="gstin" type="text" maxlength="15" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="22AAAAA0000A1Z5" />
                    @error('gstin') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GST Type</label>
                    <select wire:model="gst_type" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="regular">Regular</option>
                        <option value="unregistered">Unregistered</option>
                        <option value="consumer">Consumer</option>
                        <option value="overseas">Overseas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">PAN</label>
                    <input wire:model="pan" type="text" maxlength="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-mono uppercase focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" placeholder="ABCDE1234F" />
                    @error('pan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        {{-- Billing Address --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Billing Address</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                    <input wire:model="billing_street" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input wire:model="billing_city" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select wire:model.live="billing_state_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Select State</option>
                        @foreach(config('states') as $id => $name)
                            <option value="{{ $id }}">{{ $name }} ({{ $id }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                    <input wire:model="billing_pincode" type="text" maxlength="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input wire:model="billing_country" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
            </div>
        </div>

        {{-- Shipping Address --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Shipping Address</h2>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model.live="copyBilling" type="checkbox" class="w-4 h-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500" />
                    <span class="text-sm text-gray-600">Same as billing</span>
                </label>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Street</label>
                    <input wire:model="shipping_street" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input wire:model="shipping_city" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select wire:model.live="shipping_state_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="">Select State</option>
                        @foreach(config('states') as $id => $name)
                            <option value="{{ $id }}">{{ $name }} ({{ $id }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pincode</label>
                    <input wire:model="shipping_pincode" type="text" maxlength="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input wire:model="shipping_country" type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                </div>
            </div>
        </div>

        {{-- Financial --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Financial</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Credit Limit</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">₹</span>
                        <input wire:model="credit_limit" type="number" step="0.01" min="0" class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                    <select wire:model="currency" class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                        <option value="INR">INR - Indian Rupee</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="EUR">EUR - Euro</option>
                        <option value="GBP">GBP - British Pound</option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('admin.customers.index') }}" class="px-6 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button
                type="submit"
                class="px-6 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-75 cursor-wait"
            >
                <span wire:loading.remove>{{ $customerId ? 'Update Customer' : 'Create Customer' }}</span>
                <span wire:loading>Saving...</span>
            </button>
        </div>
    </form>
</div>
