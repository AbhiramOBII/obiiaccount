<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Livewire\Volt\Volt;
use App\Models\SalesDocument;
use App\Helpers\NumberToWords;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\ImportController;

Route::get('/', function () {
    return redirect()->route('admin.login');
});

// Guest routes
Route::middleware('guest')->group(function () {
    Volt::route('/login', 'auth.login')->name('admin.login');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Volt::route('/dashboard', 'admin.dashboard')->name('admin.dashboard');

    // Customers
    Route::middleware('permission:customers.read')->group(function () {
        Volt::route('/customers', 'admin.customers.index')->name('admin.customers.index');
        Volt::route('/customers/create', 'admin.customers.form')->name('admin.customers.create');
        Volt::route('/customers/{id}/edit', 'admin.customers.form')->name('admin.customers.edit');
        Volt::route('/customers/import', 'admin.customers.import')->name('admin.customers.import');
    });

    Route::get('/customers/import/template', function () {
        $headers = ['name','contact_name','phone','mobile','email','gstin','gst_type','pan','billing_street','billing_city','billing_state_id','billing_state_name','billing_pincode','billing_country','shipping_street','shipping_city','shipping_state_id','shipping_state_name','shipping_pincode','shipping_country','credit_limit','currency'];
        $callback = function () use ($headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            fputcsv($file, ['Sample Company', 'John Doe', '011-12345678', '9876543210', 'john@example.com', '22AAAAA0000A1Z5', 'regular', 'ABCDE1234F', '123 Main St', 'Mumbai', '27', 'Maharashtra', '400001', 'India', '123 Main St', 'Mumbai', '27', 'Maharashtra', '400001', 'India', '50000', 'INR']);
            fclose($file);
        };
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="customers_import_template.csv"',
        ]);
    })->name('admin.customers.import.template');

    // Sales
    Route::middleware('permission:sales.read')->group(function () {
        Volt::route('/sales', 'admin.sales.index')->name('admin.sales.index');
        Volt::route('/sales/create', 'admin.sales.form')->name('admin.sales.create');
        Volt::route('/sales/{id}/edit', 'admin.sales.form')->name('admin.sales.edit');
    });

    Route::get('/sales/{id}/pdf', function (int $id) {
        $doc = SalesDocument::with('items')->findOrFail($id);
        $typeLabels = ['estimate' => 'Estimate', 'proforma' => 'Proforma Invoice', 'invoice' => 'Invoice'];
        $typeLabel = $typeLabels[$doc->document_type] ?? 'Document';

        $currency = $doc->currency ?? 'INR';
        $exchangeRate = 1.0;
        $currencySymbols = ['INR' => '₹', 'USD' => '$', 'EUR' => '€', 'GBP' => '£', 'CAD' => 'C$', 'AUD' => 'A$'];
        $currencySymbol = $currencySymbols[$currency] ?? $currency . ' ';

        if ($currency !== 'INR') {
            $data = \Illuminate\Support\Facades\Cache::remember('exchange_rates_inr', 3600, function () {
                $response = \Illuminate\Support\Facades\Http::timeout(5)->get('https://open.er-api.com/v6/latest/INR');
                return $response->successful() ? $response->json() : null;
            });
            if ($data && isset($data['rates'][$currency]) && $data['rates'][$currency] > 0) {
                $exchangeRate = round(1 / $data['rates'][$currency], 2);
            }
        }

        $grandTotalInr = $doc->grand_total * $exchangeRate;
        $totalInWords = $currency === 'INR'
            ? NumberToWords::convert((float) $doc->grand_total)
            : NumberToWords::convert((float) $grandTotalInr);

        $pdf = Pdf::loadView('pdf.sales-document', compact('doc', 'typeLabel', 'totalInWords', 'currency', 'currencySymbol', 'exchangeRate', 'grandTotalInr'));
        $pdf->setPaper('A4');

        return $pdf->download($doc->document_number . '.pdf');
    })->name('admin.sales.pdf');

    // User Management
    Route::middleware('permission:users.read')->group(function () {
        Volt::route('/users', 'admin.users.index')->name('admin.users.index');
        Volt::route('/users/create', 'admin.users.form')->name('admin.users.create');
        Volt::route('/users/{id}/edit', 'admin.users.form')->name('admin.users.edit');
    });

    // Role Management
    Route::middleware('permission:roles.read')->group(function () {
        Volt::route('/roles', 'admin.roles.index')->name('admin.roles.index');
        Volt::route('/roles/create', 'admin.roles.form')->name('admin.roles.create');
        Volt::route('/roles/{id}/edit', 'admin.roles.form')->name('admin.roles.edit');
    });

    // Import
    Route::get('/import', [ImportController::class, 'show'])->name('admin.import');
    Route::post('/import', [ImportController::class, 'process'])->name('admin.import.process');

    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('admin.login');
    })->name('admin.logout');
});
