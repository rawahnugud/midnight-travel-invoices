<?php

namespace App\Http\Controllers;

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

        $revenue = (clone $query)->where('status', 'paid')->with('lineItems')->get()
            ->groupBy('currency')->map(fn ($invoices) => $invoices->sum('total'))->sortKeys();
        $recent = (clone $query)->with(['creator', 'lineItems'])->latest()->take(10)->get();

        return view('dashboard', [
            'stats' => ['total_invoices' => $totalInvoices, 'revenue' => $revenue],
            'recentInvoices' => $recent,
        ]);
    }
}
