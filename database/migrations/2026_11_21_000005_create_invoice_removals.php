<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_removals', function (Blueprint $table) {
            $table->foreignId('invoice_id')->primary()->constrained()->restrictOnDelete();
            $table->foreignId('removed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('removed_at');
        });
    }

    public function down(): void
    {
        if (DB::table('invoice_removals')->exists()) {
            throw new RuntimeException('Cannot remove the invoice deletion history.');
        }
        Schema::dropIfExists('invoice_removals');
    }
};
