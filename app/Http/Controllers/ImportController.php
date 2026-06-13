<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\SalesDocument;
use App\Models\SalesDocumentItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ImportController extends Controller
{
    public function show()
    {
        return view('pages.admin.import.index');
    }

    public function process(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ], [
            'csv_file.required' => 'Please select a CSV file.',
            'csv_file.mimes' => 'Only CSV files are accepted.',
            'csv_file.max' => 'File size must not exceed 10MB.',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Unable to read the file.');
        }

        // Read header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return back()->with('error', 'File appears to be empty.');
        }

        // Trim BOM and whitespace from headers
        $headers = array_map(function ($h) {
            return trim(preg_replace('/^\x{FEFF}/u', '', $h));
        }, $headers);

        // Read all rows
        $allRows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) === count($headers)) {
                $allRows[] = array_combine($headers, $row);
            }
        }
        fclose($handle);

        // Group rows by Invoice No
        $grouped = [];
        foreach ($allRows as $row) {
            $invoiceNo = trim($row['Invoice No'] ?? '');
            if ($invoiceNo === '') continue;
            $grouped[$invoiceNo][] = $row;
        }

        // Process in a transaction
        $results = [
            'total_rows' => count($allRows),
            'invoices_imported' => 0,
            'items_imported' => 0,
            'customers_created' => [],
            'skipped_duplicates' => [],
            'skipped_errors' => [],
        ];

        DB::beginTransaction();
        try {
            foreach ($grouped as $invoiceNo => $rows) {
                // Skip duplicates already in DB
                if (SalesDocument::where('document_number', $invoiceNo)->exists()) {
                    $results['skipped_duplicates'][] = $invoiceNo;
                    continue;
                }

                $firstRow = $rows[0];

                // Validate customer name
                $contactName = trim($firstRow['Contact Name'] ?? '');
                if ($contactName === '') {
                    $results['skipped_errors'][] = "Missing customer name on Invoice No {$invoiceNo}";
                    continue;
                }

                // Resolve or create customer
                $customer = Customer::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($contactName)])->first();
                if (!$customer) {
                    $gstin = trim($firstRow['GST No'] ?? '');
                    $customer = Customer::create([
                        'name' => $contactName,
                        'gstin' => $gstin ?: null,
                        'gst_type' => $gstin ? 'regular' : 'unregistered',
                        'is_active' => true,
                        'currency' => 'INR',
                    ]);
                    $results['customers_created'][] = $contactName;
                }

                // Determine tax type from first non-empty Tax 1 Name
                $taxType = 'cgst_sgst'; // default
                foreach ($rows as $r) {
                    $tax1Name = strtolower(trim($r['Tax 1 Name'] ?? ''));
                    if ($tax1Name !== '') {
                        if (str_contains($tax1Name, 'igst')) {
                            $taxType = 'igst';
                        } else {
                            $taxType = 'cgst_sgst';
                        }
                        break;
                    }
                }

                // Map status
                $rawStatus = strtolower(trim($firstRow['Status'] ?? ''));
                $statusMap = ['sent' => 'sent', 'draft' => 'draft', 'paid' => 'paid', 'cancelled' => 'cancelled'];
                $status = $statusMap[$rawStatus] ?? 'sent';

                // Parse dates
                $documentDate = $this->parseDate($firstRow['Date'] ?? '');
                $dueDate = $this->parseDate($firstRow['Due Date'] ?? '');

                // Inclusive tax
                $inclusiveTax = strtolower(trim($firstRow['Inclusive Tax'] ?? '')) === 'true';

                // Totals from first row (invoice-level)
                $subtotal = (float) ($firstRow['Taxable Amount'] ?? 0);
                $taxTotal = (float) ($firstRow['Tax Total'] ?? 0);
                $roundOff = (float) ($firstRow['Round Off'] ?? 0);
                $total = (float) ($firstRow['Total'] ?? 0);

                // Create sales document
                $doc = SalesDocument::create([
                    'document_type' => 'invoice',
                    'document_number' => $invoiceNo,
                    'document_sequence' => $this->extractSequence($invoiceNo),
                    'reference_number' => trim($firstRow['Reference No'] ?? '') ?: null,
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'billing_street' => $customer->billing_street ?? '',
                    'billing_city' => $customer->billing_city ?? '',
                    'billing_state_id' => $customer->billing_state_id,
                    'billing_state_name' => $customer->billing_state_name ?? '',
                    'billing_pincode' => $customer->billing_pincode ?? '',
                    'billing_country' => $customer->billing_country ?? 'India',
                    'gstin' => $customer->gstin ?? '',
                    'place_of_supply_id' => $customer->billing_state_id,
                    'place_of_supply_name' => $customer->billing_state_name ?? '',
                    'document_date' => $documentDate,
                    'due_date' => $dueDate,
                    'tax_type' => $taxType,
                    'subtotal' => $subtotal,
                    'cgst_total' => $taxType === 'cgst_sgst' ? round($taxTotal / 2, 2) : 0,
                    'sgst_total' => $taxType === 'cgst_sgst' ? round($taxTotal / 2, 2) : 0,
                    'igst_total' => $taxType === 'igst' ? $taxTotal : 0,
                    'grand_total' => $total,
                    'round_off' => $roundOff,
                    'inclusive_tax' => $inclusiveTax,
                    'notes' => trim($firstRow['Notes'] ?? '') ?: null,
                    'terms' => trim($firstRow['Terms & Conditions'] ?? '') ?: null,
                    'status' => $status,
                    'currency' => 'INR',
                ]);

                // Create line items
                foreach ($rows as $sortOrder => $row) {
                    $tax1Rate = (float) ($row['Tax 1 Rate'] ?? 0);
                    $tax2Rate = (float) ($row['Tax 2 Rate'] ?? 0);
                    $taxRate = $tax1Rate + $tax2Rate;

                    $tax1Amount = (float) ($row['Tax 1 Amount'] ?? 0);
                    $tax2Amount = (float) ($row['Tax 2 Amount'] ?? 0);
                    $lineTaxAmount = $tax1Amount + $tax2Amount;

                    $itemTaxableAmount = (float) ($row['Item Taxable Amount'] ?? 0);
                    $lineTotal = $itemTaxableAmount + $lineTaxAmount;

                    // Determine per-item tax split
                    $cgstAmount = 0;
                    $sgstAmount = 0;
                    $igstAmount = 0;

                    if ($taxType === 'cgst_sgst') {
                        $cgstAmount = $tax1Amount;
                        $sgstAmount = $tax2Amount;
                    } else {
                        $igstAmount = $lineTaxAmount;
                    }

                    // Tax treatment: nonTaxable = 0%
                    $taxTreatment = strtolower(trim($row['Tax Treatment'] ?? ''));
                    if ($taxTreatment === 'nontaxable') {
                        $taxRate = 0;
                        $lineTaxAmount = 0;
                        $cgstAmount = 0;
                        $sgstAmount = 0;
                        $igstAmount = 0;
                        $lineTotal = $itemTaxableAmount;
                    }

                    SalesDocumentItem::create([
                        'sales_document_id' => $doc->id,
                        'sort_order' => $sortOrder,
                        'description' => trim($row['Description'] ?? $row['Item Name'] ?? ''),
                        'hsn_sac' => trim($row['HSN/SAC'] ?? '') ?: null,
                        'quantity' => (float) ($row['Qty'] ?? 1),
                        'unit' => trim($row['Unit'] ?? '') ?: 'NOS',
                        'rate' => (float) ($row['Rate'] ?? 0),
                        'tax_percent' => $taxRate,
                        'amount' => $itemTaxableAmount,
                        'cgst_amount' => $cgstAmount,
                        'sgst_amount' => $sgstAmount,
                        'igst_amount' => $igstAmount,
                        'total' => $lineTotal,
                    ]);

                    $results['items_imported']++;
                }

                $results['invoices_imported']++;
            }

            // Update document sequence counter
            $this->updateSequenceCounter();

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Import failed: ' . $e->getMessage());
        }

        return view('pages.admin.import.index', ['results' => $results]);
    }

    private function parseDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') return null;

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            // Try common formats
            foreach (['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y-m-d', 'd M Y'] as $fmt) {
                try {
                    return Carbon::createFromFormat($fmt, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
            return null;
        }
    }

    private function extractSequence(string $invoiceNo): int
    {
        // Extract numeric part from invoice number
        preg_match('/(\d+)/', $invoiceNo, $matches);
        return (int) ($matches[1] ?? 0);
    }

    private function updateSequenceCounter(): void
    {
        // Find the highest invoice sequence number
        $maxSeq = SalesDocument::where('document_type', 'invoice')
            ->max('document_sequence');

        DB::table('document_sequences')->updateOrInsert(
            ['type' => 'invoice'],
            ['last_number' => $maxSeq ?? 0, 'updated_at' => now()]
        );

        // Also update estimate and proforma sequences
        foreach (['estimate', 'proforma'] as $type) {
            $max = SalesDocument::where('document_type', $type)->max('document_sequence');
            if ($max) {
                DB::table('document_sequences')->updateOrInsert(
                    ['type' => $type],
                    ['last_number' => $max, 'updated_at' => now()]
                );
            }
        }
    }
}
