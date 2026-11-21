<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('prior_invoice_status', 16)->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->unsignedBigInteger('reversed_by')->nullable();
            $table->text('reversal_reason')->nullable();
        });
    }

    public function down(): void
    {
        if (DB::table('payments')->whereNotNull('reversed_at')->exists()) {
            throw new RuntimeException('Cannot remove recorded payment reversals.');
        }
        foreach (['prior_invoice_status', 'reversed_at', 'reversed_by', 'reversal_reason'] as $column) {
            Schema::table('payments', fn (Blueprint $table) => $table->dropColumn($column));
        }
    }
};
