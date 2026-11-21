<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->index('invoice_id');
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10);
            $table->date('paid_on');
            $table->string('method', 16);
            $table->string('reference')->nullable();
            $table->decimal('balance_after', 15, 2);
            $table->string('customer_name');
            $table->string('issuer_name');
            $table->string('invoice_number');
            $table->string('branch', 2)->nullable();
            $table->uuid('request_key')->unique();
            $table->foreignId('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::table('payments')->exists()) {
            throw new RuntimeException('Cannot remove recorded payments and issued receipts.');
        }
        Schema::dropIfExists('payments');
    }
};
