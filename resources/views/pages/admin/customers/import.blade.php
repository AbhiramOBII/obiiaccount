<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Models\Customer;

new #[Layout('components.layouts.admin')] class extends Component
{
    use WithFileUploads;

    public $csvFile = null;
    public array $headers = [];
    public array $rows = [];
    public array $columnMap = [];
    public int $importedCount = 0;
    public int $skippedCount = 0;
    public bool $hasPreview = false;
    public bool $importComplete = false;
    public array $importErrors = [];

    public array $availableFields = [
        '' => '-- Skip --',
        'name' => 'Customer Name',
        'contact_name' => 'Contact Person',
        'phone' => 'Phone',
        'mobile' => 'Mobile',
        'email' => 'Email',
        'gstin' => 'GSTIN',
        'gst_type' => 'GST Type',
        'pan' => 'PAN',
        'billing_street' => 'Billing Street',
        'billing_city' => 'Billing City',
        'billing_state_id' => 'Billing State Code',
        'billing_state_name' => 'Billing State Name',
        'billing_pincode' => 'Billing Pincode',
        'billing_country' => 'Billing Country',
        'shipping_street' => 'Shipping Street',
        'shipping_city' => 'Shipping City',
        'shipping_state_id' => 'Shipping State Code',
        'shipping_state_name' => 'Shipping State Name',
        'shipping_pincode' => 'Shipping Pincode',
        'shipping_country' => 'Shipping Country',
        'credit_limit' => 'Credit Limit',
        'currency' => 'Currency',
    ];

    public function updatedCsvFile(): void
    {
        $this->validate([
            'csvFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $this->parseFile();
    }

    public function parseFile(): void
    {
        $this->reset(['headers', 'rows', 'columnMap', 'importedCount', 'skippedCount', 'importComplete', 'importErrors']);

        $path = $this->csvFile->getRealPath();
        $handle = fopen($path, 'r');

        if (!$handle) {
            $this->addError('csvFile', 'Unable to read the file.');
            return;
        }

        $this->headers = fgetcsv($handle);
        if (!$this->headers) {
            $this->addError('csvFile', 'File appears to be empty or invalid.');
            fclose($handle);
            return;
        }

        $this->headers = array_map('trim', $this->headers);

        // Auto-map columns by matching header names
        foreach ($this->headers as $index => $header) {
            $normalized = strtolower(str_replace([' ', '_', '-'], '', $header));
            $mapped = '';
            foreach ($this->availableFields as $field => $label) {
                $normalizedField = strtolower(str_replace([' ', '_', '-'], '', $field));
                $normalizedLabel = strtolower(str_replace([' ', '_', '-'], '', $label));
                if ($normalizedField && ($normalized === $normalizedField || $normalized === $normalizedLabel)) {
                    $mapped = $field;
                    break;
                }
            }
            $this->columnMap[$index] = $mapped;
        }

        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== false && $rowCount < 100) {
            if (array_filter($row)) {
                $this->rows[] = array_map('trim', $row);
                $rowCount++;
            }
        }

        fclose($handle);
        $this->hasPreview = true;
    }

    public function import(): void
    {
        // Validate that 'name' is mapped
        if (!in_array('name', $this->columnMap)) {
            $this->addError('csvFile', 'You must map at least the "Customer Name" column.');
            return;
        }

        $this->importedCount = 0;
        $this->skippedCount = 0;
        $this->importErrors = [];

        $validGstTypes = ['regular', 'unregistered', 'consumer', 'overseas'];

        foreach ($this->rows as $rowIndex => $row) {
            try {
                $data = [];
                foreach ($this->columnMap as $colIndex => $field) {
                    if ($field && isset($row[$colIndex])) {
                        $data[$field] = $row[$colIndex];
                    }
                }

                if (empty($data['name'])) {
                    $this->skippedCount++;
                    $this->importErrors[] = "Row " . ($rowIndex + 2) . ": Name is empty, skipped.";
                    continue;
                }

                // Normalize gst_type
                if (isset($data['gst_type'])) {
                    $data['gst_type'] = strtolower(trim($data['gst_type']));
                    if (!in_array($data['gst_type'], $validGstTypes)) {
                        $data['gst_type'] = 'unregistered';
                    }
                }

                // Resolve state: if state_name given but no state_id, look up the ID
                $states = config('states');
                $statesFlipped = array_map('strtolower', $states);

                foreach (['billing', 'shipping'] as $prefix) {
                    $idKey = $prefix . '_state_id';
                    $nameKey = $prefix . '_state_name';

                    if (!empty($data[$nameKey]) && empty($data[$idKey])) {
                        $found = array_search(strtolower(trim($data[$nameKey])), $statesFlipped);
                        if ($found !== false) {
                            $data[$idKey] = $found;
                            $data[$nameKey] = $states[$found];
                        }
                    } elseif (!empty($data[$idKey]) && empty($data[$nameKey])) {
                        $id = (int) $data[$idKey];
                        if (isset($states[$id])) {
                            $data[$nameKey] = $states[$id];
                        }
                    }
                }

                // Defaults
                if (!isset($data['billing_country']) || empty($data['billing_country'])) {
                    $data['billing_country'] = 'India';
                }
                if (!isset($data['shipping_country']) || empty($data['shipping_country'])) {
                    $data['shipping_country'] = 'India';
                }
                if (!isset($data['currency']) || empty($data['currency'])) {
                    $data['currency'] = 'INR';
                }
                if (isset($data['credit_limit'])) {
                    $data['credit_limit'] = (float) preg_replace('/[^0-9.]/', '', $data['credit_limit']);
                }

                Customer::create($data);
                $this->importedCount++;
            } catch (\Exception $e) {
                $this->skippedCount++;
                $this->importErrors[] = "Row " . ($rowIndex + 2) . ": " . $e->getMessage();
            }
        }

        $this->importComplete = true;
        $this->hasPreview = false;
    }

    public function resetImport(): void
    {
        $this->reset();
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-4">
        <a href="{{ route('admin.customers.index') }}" class="p-2 text-gray-500 hover:text-primary-700 hover:bg-primary-50 rounded-lg transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Import Customers</h1>
            <p class="mt-1 text-sm text-gray-500">Upload a CSV file to bulk import customers</p>
        </div>
    </div>

    {{-- Import Complete --}}
    @if($importComplete)
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 text-center">
            <div class="mx-auto w-12 h-12 bg-green-100 rounded-full flex items-center justify-center mb-4">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-gray-900">Import Complete</h2>
            <p class="mt-2 text-sm text-gray-600">
                <span class="font-medium text-green-600">{{ $importedCount }} imported</span>
                @if($skippedCount > 0)
                    &middot; <span class="font-medium text-yellow-600">{{ $skippedCount }} skipped</span>
                @endif
            </p>

            @if(count($importErrors) > 0)
                <div class="mt-4 text-left max-w-md mx-auto bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <p class="text-sm font-medium text-yellow-800 mb-2">Issues:</p>
                    <ul class="text-xs text-yellow-700 space-y-1 max-h-40 overflow-y-auto">
                        @foreach($importErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mt-6 flex items-center justify-center gap-3">
                <a href="{{ route('admin.customers.index') }}" class="px-4 py-2 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">View Customers</a>
                <button wire:click="resetImport" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">Import More</button>
            </div>
        </div>
    @else
        {{-- Upload --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Upload CSV</h2>
                <a href="{{ route('admin.customers.import.template') }}" class="text-sm text-primary-600 hover:text-primary-800 font-medium transition-colors">
                    Download Template
                </a>
            </div>

            <div
                x-data="{ dragging: false }"
                x-on:dragover.prevent="dragging = true"
                x-on:dragleave.prevent="dragging = false"
                x-on:drop.prevent="dragging = false; $wire.upload('csvFile', $event.dataTransfer.files[0])"
                :class="dragging ? 'border-primary-500 bg-primary-50' : 'border-gray-300 bg-gray-50'"
                class="border-2 border-dashed rounded-lg p-8 text-center transition-colors cursor-pointer"
                @click="$refs.fileInput.click()"
            >
                <input type="file" x-ref="fileInput" wire:model="csvFile" accept=".csv,.txt" class="hidden" />

                <div wire:loading.remove wire:target="csvFile">
                    <svg class="w-10 h-10 mx-auto text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="text-sm text-gray-600">Drag & drop your CSV file here, or <span class="text-primary-600 font-medium">browse</span></p>
                    <p class="text-xs text-gray-400 mt-1">Max 2MB &middot; .csv format</p>
                </div>

                <div wire:loading wire:target="csvFile" class="text-sm text-gray-500">
                    <svg class="w-6 h-6 mx-auto mb-2 animate-spin text-primary-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Reading file...
                </div>
            </div>

            @error('csvFile') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- Preview & Mapping --}}
        @if($hasPreview)
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Map Columns</h2>
                        <p class="text-sm text-gray-500">{{ count($rows) }} rows found. Map CSV columns to customer fields.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200">
                                @foreach($headers as $index => $header)
                                    <th class="px-3 py-2 text-left min-w-[180px]">
                                        <div class="text-xs text-gray-500 mb-1 font-normal">{{ $header }}</div>
                                        <select wire:model="columnMap.{{ $index }}" class="w-full px-2 py-1.5 border border-gray-300 rounded-md text-xs focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none">
                                            @foreach($availableFields as $value => $label)
                                                <option value="{{ $value }}">{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach(array_slice($rows, 0, 5) as $row)
                                <tr class="text-gray-600">
                                    @foreach($headers as $index => $header)
                                        <td class="px-3 py-2 text-xs truncate max-w-[200px]">{{ $row[$index] ?? '' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if(count($rows) > 5)
                    <p class="mt-2 text-xs text-gray-400">Showing 5 of {{ count($rows) }} rows</p>
                @endif

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button wire:click="resetImport" class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </button>
                    <button
                        wire:click="import"
                        wire:confirm="Import {{ count($rows) }} customers?"
                        class="px-6 py-2 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-75 cursor-wait"
                    >
                        <span wire:loading.remove wire:target="import">Import {{ count($rows) }} Customers</span>
                        <span wire:loading wire:target="import">Importing...</span>
                    </button>
                </div>
            </div>
        @endif
    @endif
</div>
