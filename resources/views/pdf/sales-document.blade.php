<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; line-height: 1.4; }
        .page { padding: 30px 40px; }

        /* Header */
        .company-name { font-size: 22px; font-weight: bold; color: #151828; margin-bottom: 6px; }
        .company-details { font-size: 9px; color: #555; line-height: 1.5; margin-bottom: 20px; }

        /* Info section */
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .info-table td { vertical-align: top; padding: 6px 10px; border: 1px solid #b8a9d4; }
        .info-table .label-cell { background: #e8dff5; font-weight: bold; font-size: 10px; }
        .info-table .bill-to { width: 55%; }
        .info-table .doc-info { width: 45%; }

        .customer-name { font-weight: bold; font-size: 12px; margin-bottom: 4px; }
        .customer-details { font-size: 10px; color: #444; line-height: 1.5; }
        .gstin-label { font-weight: bold; }

        .doc-field { margin-bottom: 3px; font-size: 10px; }
        .doc-field-label { font-weight: bold; }

        .pos-row { font-size: 10px; padding: 4px 10px; border: 1px solid #b8a9d4; border-top: none; }

        /* Items table */
        .items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .items-table th {
            background: #e8dff5;
            border: 1px solid #b8a9d4;
            padding: 6px 5px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .items-table td {
            border: 1px solid #b8a9d4;
            padding: 6px 5px;
            font-size: 10px;
            vertical-align: top;
        }
        .items-table .text-right { text-align: right; }
        .items-table .text-center { text-align: center; }
        .items-table .text-left { text-align: left; }

        .items-table .total-row td {
            background: #e8dff5;
            font-weight: bold;
            border-top: 2px solid #b8a9d4;
        }

        /* Empty rows for spacing */
        .items-table .empty-row td { height: 20px; }

        /* Summary */
        .summary-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .summary-table td { padding: 4px 10px; border: 1px solid #b8a9d4; font-size: 10px; }
        .summary-table .label { text-align: left; }
        .summary-table .value { text-align: right; font-weight: bold; }
        .summary-table .total-label { font-weight: bold; font-size: 11px; }
        .summary-table .total-value { font-weight: bold; font-size: 11px; }

        /* Footer */
        .footer-table { width: 100%; border-collapse: collapse; margin-top: 0; }
        .footer-table td { padding: 5px 10px; border: 1px solid #b8a9d4; font-size: 9px; vertical-align: top; }
        .bank-details { line-height: 1.6; }
        .signature-block { text-align: right; }
        .signature-label { font-weight: bold; margin-bottom: 30px; display: block; }
        .signature-line { font-size: 9px; color: #666; }

        .disclaimer { text-align: center; font-size: 8px; color: #888; margin-top: 8px; }
    </style>
</head>
<body>
    <div class="page">
        {{-- Company Header --}}
        <div class="company-name">Obii KriationZ Web LLP</div>
        <div class="company-details">
            #3500/A, Second Floor, 80ft Road, Above Union Bank, Raghuvanahally, Banashankari 6th Stage, Bengaluru, Karnataka, India - 560062<br>
            M: 9964331200, Email: info@obiikriationz.com<br>
            GSTIN: 29AAFF04247G1Z4, PAN: AAFFO4247G
        </div>

        {{-- Bill To & Document Info --}}
        <table class="info-table">
            <tr>
                <td class="label-cell bill-to">Bill To</td>
                <td class="label-cell doc-info">{{ $typeLabel }}</td>
            </tr>
            <tr>
                <td class="bill-to" rowspan="2">
                    <div class="customer-name">{{ $doc->customer_name }}</div>
                    <div class="customer-details">
                        @if($doc->billing_street) {{ $doc->billing_street }},<br> @endif
                        {{ collect([$doc->billing_city, $doc->billing_state_name, $doc->billing_pincode])->filter()->implode(', ') }}
                        @if($doc->billing_country && $doc->billing_country !== 'India'), {{ $doc->billing_country }} @endif
                        @if($doc->billing_state_id)
                            <br>Place of Supply: {{ $doc->billing_state_name }} ({{ $doc->billing_state_id }})
                        @endif
                        @if($doc->gstin)
                            <br><span class="gstin-label">GSTIN: {{ $doc->gstin }}</span>
                        @endif
                    </div>
                </td>
                <td class="doc-info">
                    <div class="doc-field"><span class="doc-field-label">{{ $typeLabel }} No:</span> {{ $doc->document_number }}</div>
                    <div class="doc-field"><span class="doc-field-label">{{ $typeLabel }} Date:</span> {{ $doc->document_date->format('d/n/Y') }}</div>
                </td>
            </tr>
            <tr>
                <td class="doc-info">
                    <div class="doc-field"><span class="doc-field-label">Payment Terms:</span> Due on Receipt</div>
                    @if($doc->due_date)
                        <div class="doc-field"><span class="doc-field-label">Due Date:</span> {{ $doc->due_date->format('d/n/Y') }}</div>
                    @endif
                </td>
            </tr>
        </table>

        @if($doc->place_of_supply_id)
            <div class="pos-row">
                Place of Supply: {{ strtoupper(substr($doc->place_of_supply_name ?? '', 0, 2)) }}({{ $doc->place_of_supply_id }})
            </div>
        @endif

        {{-- Line Items --}}
        @if($doc->tax_type === 'cgst_sgst')
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:25px;">Sr.</th>
                        <th style="width:160px;" class="text-left">Item & Description</th>
                        <th>HSN/SAC</th>
                        <th>Qty</th>
                        <th>Rate ({{ $currencySymbol }})</th>
                        <th>CGST %</th>
                        <th>CGST Amt ({{ $currencySymbol }})</th>
                        <th>SGST %</th>
                        <th>SGST Amt ({{ $currencySymbol }})</th>
                        <th>Amount ({{ $currencySymbol }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doc->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-left">{{ $item->description }}</td>
                            <td class="text-center">{{ $item->hsn_sac }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}<br>{{ $item->unit }}</td>
                            <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($item->tax_percent / 2, 2), '0'), '.') }}%</td>
                            <td class="text-right">{{ number_format($item->cgst_amount, 2) }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($item->tax_percent / 2, 2), '0'), '.') }}%</td>
                            <td class="text-right">{{ number_format($item->sgst_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach

                    @for($i = count($doc->items); $i < 5; $i++)
                        <tr class="empty-row"><td colspan="10">&nbsp;</td></tr>
                    @endfor

                    <tr class="total-row">
                        <td colspan="3" class="text-center">Total</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($doc->items->sum('quantity'), 2), '0'), '.') }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($doc->cgst_total, 2) }}</td>
                        <td></td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($doc->sgst_total, 2) }}</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($doc->subtotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @else
            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width:25px;">Sr.</th>
                        <th style="width:180px;" class="text-left">Item & Description</th>
                        <th>HSN/SAC</th>
                        <th>Qty</th>
                        <th>Rate ({{ $currencySymbol }})</th>
                        <th>IGST %</th>
                        <th>IGST Amt ({{ $currencySymbol }})</th>
                        <th>Amount ({{ $currencySymbol }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($doc->items as $index => $item)
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td class="text-left">{{ $item->description }}</td>
                            <td class="text-center">{{ $item->hsn_sac }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($item->quantity, 2), '0'), '.') }}<br>{{ $item->unit }}</td>
                            <td class="text-right">{{ number_format($item->rate, 2) }}</td>
                            <td class="text-center">{{ rtrim(rtrim(number_format($item->tax_percent, 2), '0'), '.') }}%</td>
                            <td class="text-right">{{ number_format($item->igst_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                    @endforeach

                    @for($i = count($doc->items); $i < 5; $i++)
                        <tr class="empty-row"><td colspan="8">&nbsp;</td></tr>
                    @endfor

                    <tr class="total-row">
                        <td colspan="3" class="text-center">Total</td>
                        <td class="text-center">{{ rtrim(rtrim(number_format($doc->items->sum('quantity'), 2), '0'), '.') }}</td>
                        <td></td>
                        <td></td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($doc->igst_total, 2) }}</td>
                        <td class="text-right">{{ $currencySymbol }}{{ number_format($doc->subtotal, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        @endif

        {{-- Summary & Footer --}}
        @php
            $summaryRows = $doc->tax_type === 'cgst_sgst' ? 4 : 3;
            if ($currency !== 'INR') $summaryRows++;
        @endphp
        <table class="summary-table">
            <tr>
                <td class="label" rowspan="{{ $summaryRows }}" style="width: 55%;">
                    <strong>Total in words:</strong><br>
                    {{ $totalInWords }}
                </td>
                <td class="label">Subtotal:</td>
                <td class="value">{{ $currencySymbol }}{{ number_format($doc->subtotal, 2) }}</td>
            </tr>
            @if($doc->tax_type === 'cgst_sgst')
                <tr>
                    <td class="label">CGST {{ rtrim(rtrim(number_format(($doc->items->first()->tax_percent ?? 18) / 2, 2), '0'), '.') }}%:</td>
                    <td class="value">{{ $currencySymbol }}{{ number_format($doc->cgst_total, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">SGST {{ rtrim(rtrim(number_format(($doc->items->first()->tax_percent ?? 18) / 2, 2), '0'), '.') }}%:</td>
                    <td class="value">{{ $currencySymbol }}{{ number_format($doc->sgst_total, 2) }}</td>
                </tr>
            @else
                <tr>
                    <td class="label">IGST {{ rtrim(rtrim(number_format($doc->items->first()->tax_percent ?? 18, 2), '0'), '.') }}%:</td>
                    <td class="value">{{ $currencySymbol }}{{ number_format($doc->igst_total, 2) }}</td>
                </tr>
            @endif
            <tr>
                <td class="total-label">Total ({{ $currency }}):</td>
                <td class="total-value">{{ $currencySymbol }}{{ number_format($doc->grand_total, 2) }}</td>
            </tr>
            @if($currency !== 'INR')
                <tr>
                    <td class="total-label">INR Equivalent (@ ₹{{ number_format($exchangeRate, 2) }}):</td>
                    <td class="total-value">₹{{ number_format($grandTotalInr, 2) }}</td>
                </tr>
            @endif
        </table>

        <table class="footer-table">
            <tr>
                <td style="width: 55%;">
                    <strong>Bank Details:</strong><br>
                    <div class="bank-details">
                        Name of Bank: HDFC Bank<br>
                        Account Name: OBII KRIATIONZ WEB LLP<br>
                        Account Number: 50200025946370<br>
                        IFSC code: hdfc0000875
                    </div>
                </td>
                <td class="signature-block">
                    <span class="signature-label">For, Obii KriationZ Web LLP</span>
                    <br><br><br>
                    <span class="signature-line">Authorized signature</span>
                </td>
            </tr>
            @if($doc->notes)
                <tr>
                    <td colspan="2">Notes: {{ $doc->notes }}</td>
                </tr>
            @endif
            @if($doc->terms)
                <tr>
                    <td colspan="2">Terms & Conditions: {{ $doc->terms }}</td>
                </tr>
            @endif
        </table>

        <div class="disclaimer">*This is a computer generated receipt</div>
    </div>
</body>
</html>
