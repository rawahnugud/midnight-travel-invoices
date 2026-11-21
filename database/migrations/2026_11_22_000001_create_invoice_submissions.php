<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_submissions', function (Blueprint $table) {
            $table->uuid('request_key')->primary();
            $table->string('fingerprint', 64);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_submissions');
    }
};
