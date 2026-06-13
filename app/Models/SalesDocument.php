<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class SalesDocument extends Model
{
    protected $fillable = [
        'document_type', 'document_number', 'document_sequence', 'reference_number',
        'customer_id', 'customer_name', 'user_id',
        'billing_street', 'billing_city', 'billing_state_id', 'billing_state_name',
        'billing_pincode', 'billing_country', 'gstin',
        'place_of_supply_id', 'place_of_supply_name',
        'document_date', 'due_date',
        'tax_type',
        'subtotal', 'cgst_total', 'sgst_total', 'igst_total', 'grand_total',
        'round_off', 'inclusive_tax',
        'currency', 'notes', 'terms', 'status',
        'converted_from_id',
    ];

    protected $casts = [
        'document_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'cgst_total' => 'decimal:2',
        'sgst_total' => 'decimal:2',
        'igst_total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'round_off' => 'decimal:2',
        'inclusive_tax' => 'boolean',
        'document_sequence' => 'integer',
        'billing_state_id' => 'integer',
        'place_of_supply_id' => 'integer',
    ];

    public const TYPE_PREFIXES = [
        'estimate' => 'EST',
        'proforma' => 'PI',
        'invoice' => 'INV',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesDocumentItem::class)->orderBy('sort_order');
    }

    public function convertedFrom(): BelongsTo
    {
        return $this->belongsTo(self::class, 'converted_from_id');
    }

    public static function generateNumber(string $type): array
    {
        // Take the higher of document_sequences table and actual max in sales_documents
        $seqRow = DB::table('document_sequences')->where('type', $type)->first();
        $seqLast = $seqRow ? (int) $seqRow->last_number : 0;
        $docLast = (int) (self::where('document_type', $type)->max('document_sequence') ?? 0);
        $lastSeq = max($seqLast, $docLast);

        $nextSeq = $lastSeq + 1;
        $number = '00' . str_pad($nextSeq, 4, '0', STR_PAD_LEFT);

        return ['number' => $number, 'sequence' => $nextSeq];
    }
}
