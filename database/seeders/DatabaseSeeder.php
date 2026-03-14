<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\LineItem;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::count() > 0) {
            return;
        }

        User::create([
            'username' => 'admin',
            'email' => 'admin@midnighttravel.net',
            'password' => Hash::make('admin123'),
            'role' => 'admin',
        ]);
        User::create([
            'username' => 'staff',
            'email' => 'staff@midnighttravel.net',
            'password' => Hash::make('staff123'),
            'role' => 'staff',
        ]);

        $adminId = User::where('username', 'admin')->value('id');
        $staffId = User::where('username', 'staff')->value('id');

        if (Invoice::count() === 0 && $adminId && $staffId) {
            $now = now()->format('Y-m-d');

            $inv1 = Invoice::create([
                'invoice_number' => 'INV-2024-001',
                'status' => 'paid',
                'customer_name' => 'Acme Corp',
                'customer_email' => 'billing@acme.com',
                'customer_phone' => '+1 555 0100',
                'customer_address' => '123 Main St, City',
                'invoice_date' => $now,
                'due_date' => $now,
                'currency' => 'USD',
                'tax_rate' => 0,
                'discount_amount' => 0,
                'notes' => 'Thank you for your business.',
                'created_by' => $adminId,
            ]);
            LineItem::create(['invoice_id' => $inv1->id, 'item_name' => 'Travel Package - Desert Safari', 'description' => 'Full day safari with dinner', 'quantity' => 2, 'unit_price' => 150, 'sort_order' => 0]);
            LineItem::create(['invoice_id' => $inv1->id, 'item_name' => 'Airport Transfer', 'description' => 'Private transfer', 'quantity' => 1, 'unit_price' => 45, 'sort_order' => 1]);

            $inv2 = Invoice::create([
                'invoice_number' => 'INV-2024-002',
                'status' => 'pending',
                'customer_name' => 'Jane Smith',
                'customer_email' => 'jane@example.com',
                'invoice_date' => $now,
                'due_date' => $now,
                'currency' => 'USD',
                'tax_rate' => 0,
                'discount_amount' => 0,
                'created_by' => $staffId,
            ]);
            LineItem::create(['invoice_id' => $inv2->id, 'item_name' => 'Luxury Hotel Stay', 'description' => '3 nights - Sea View', 'quantity' => 3, 'unit_price' => 280, 'sort_order' => 0]);
        }
    }
}
