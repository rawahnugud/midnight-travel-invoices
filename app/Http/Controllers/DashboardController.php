<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $query = Invoice::query();
        if ($user->isStaff()) {
            $query->where('created_by', $user->id);
        }

        $totalInvoices = (clone $query)->count();
        $paidCount = (clone $query)->where('status', 'paid')->count();
        $pendingCount = (clone $query)->where('status', 'pending')->count();

        $revenueQuery = Invoice::where('status', 'paid')->with('lineItems');
        if ($user->isStaff()) {
            $revenueQuery->where('created_by', $user->id);
        }
        $totalRevenue = (clone $revenueQuery)->get()->sum(fn ($inv) => $inv->total);

        $recent = (clone $query)->with(['creator', 'lineItems'])->latest()->take(10)->get();

        $currency = optional(BusinessSetting::get())->default_currency ?? 'USD';

        return view('dashboard', [
            'stats' => [
                'total_invoices' => $totalInvoices,
                'paid' => $paidCount,
                'pending' => $pendingCount,
                'revenue' => $totalRevenue,
                'currency' => $currency,
            ],
            'recentInvoices' => $recent,
        ]);
    }
}
