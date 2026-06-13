<x-layouts.admin>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Import Invoices</h1>
            <p class="mt-1 text-sm text-gray-500">Upload a CSV file exported from your previous accounting app to bulk-import historical invoices.</p>
        </div>

        {{-- Error messages --}}
        @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                    <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
                </div>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Results --}}
        @if(isset($results))
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-4">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Import Complete
                </h2>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-gray-900">{{ $results['total_rows'] }}</div>
                        <div class="text-xs text-gray-500 mt-1">Total Rows Read</div>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-green-700">{{ $results['invoices_imported'] }}</div>
                        <div class="text-xs text-green-600 mt-1">Invoices Imported</div>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-blue-700">{{ $results['items_imported'] }}</div>
                        <div class="text-xs text-blue-600 mt-1">Line Items Imported</div>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-4 text-center">
                        <div class="text-2xl font-bold text-purple-700">{{ count($results['customers_created']) }}</div>
                        <div class="text-xs text-purple-600 mt-1">Customers Created</div>
                    </div>
                </div>

                @if(count($results['customers_created']) > 0)
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">New Customers Created</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($results['customers_created'] as $name)
                                <span class="inline-flex px-2.5 py-1 bg-purple-50 text-purple-700 text-xs font-medium rounded-full">{{ $name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($results['skipped_duplicates']) > 0)
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-yellow-700 mb-2">Skipped — Duplicate Invoice Numbers ({{ count($results['skipped_duplicates']) }})</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach($results['skipped_duplicates'] as $num)
                                <span class="inline-flex px-2.5 py-1 bg-yellow-50 text-yellow-700 text-xs font-medium rounded-full">{{ $num }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if(count($results['skipped_errors']) > 0)
                    <div class="border-t pt-4">
                        <h3 class="text-sm font-semibold text-red-700 mb-2">Skipped — Errors ({{ count($results['skipped_errors']) }})</h3>
                        <ul class="list-disc list-inside text-sm text-red-600 space-y-1">
                            @foreach($results['skipped_errors'] as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        {{-- Upload Form --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
            <form action="{{ route('admin.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                    <div class="flex items-center gap-4">
                        <label class="relative cursor-pointer">
                            <input type="file" name="csv_file" accept=".csv" class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2.5 file:px-5
                                file:rounded-lg file:border file:border-gray-300
                                file:text-sm file:font-medium
                                file:bg-white file:text-gray-700
                                hover:file:bg-gray-50
                                focus:outline-none" />
                        </label>
                    </div>
                    <p class="mt-2 text-xs text-gray-400">Accepted format: .csv — Max size: 10MB</p>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-amber-800 mb-1">Expected CSV Format</h4>
                    <p class="text-xs text-amber-700">
                        The CSV should have columns: Date, Invoice No, Due Date, Reference No, Contact Name, GST No,
                        Item Name, Description, HSN/SAC, Qty, Unit, Rate, Item Taxable Amount, Tax Treatment,
                        Tax 1 Name, Tax 1 Rate, Tax 1 Amount, Tax 2 Name, Tax 2 Rate, Tax 2 Amount,
                        Taxable Amount, Tax Total, Round Off, Total, Inclusive Tax, Status, Notes, Terms & Conditions.
                    </p>
                    <p class="text-xs text-amber-700 mt-1">
                        Multiple rows with the same Invoice No are grouped as line items of one invoice.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 bg-primary-800 hover:bg-primary-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Import Invoices
                    </button>
                    <a href="{{ route('admin.sales.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                        Back to Sales
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-layouts.admin>
