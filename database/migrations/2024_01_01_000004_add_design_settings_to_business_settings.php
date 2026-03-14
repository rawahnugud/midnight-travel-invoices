<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('primary_color', 20)->default('#85144b')->after('tax_id');
            $table->string('accent_color', 20)->default('#c9a227')->after('primary_color');
            $table->string('login_logo_path')->nullable()->after('accent_color');
            $table->string('invoice_header_color', 20)->nullable()->after('login_logo_path');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'accent_color', 'login_logo_path', 'invoice_header_color']);
        });
    }
};
