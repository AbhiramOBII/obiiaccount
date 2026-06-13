<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDocumentItem extends Model
{
    protected $fillable = [
        'sales_document_id', 'sort_order',
        'description', 'hsn_sac', 'quantity', 'unit', 'rate', 'tax_percent',
        'amount', 'cgst_amount', 'sgst_amount', 'igst_amount', 'total',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'rate' => 'decimal:2',
        'tax_percent' => 'decimal:2',
        'amount' => 'decimal:2',
        'cgst_amount' => 'decimal:2',
        'sgst_amount' => 'decimal:2',
        'igst_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    public function salesDocument(): BelongsTo
    {
        return $this->belongsTo(SalesDocument::class);
    }
}
