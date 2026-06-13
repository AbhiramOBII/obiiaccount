<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sales_document_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('sales_document_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->text('description');
            $table->string('hsn_sac', 20)->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit', 20)->default('Nos');
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(18);

            $table->decimal('amount', 15, 2)->default(0);       // qty * rate
            $table->decimal('cgst_amount', 15, 2)->default(0);
            $table->decimal('sgst_amount', 15, 2)->default(0);
            $table->decimal('igst_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);         // amount + tax

            $table->timestamps();

            $table->index('sales_document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_document_items');
    }
};
