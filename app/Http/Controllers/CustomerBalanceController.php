<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class CustomerBalanceController extends Controller
{
    public function __invoke(Request $request)
    {
        $input = $request->validate(['q' => 'nullable|string|max:255', 'customer' => 'nullable|string|max:255']);
        $search = trim($input['q'] ?? '');
        $customer = trim($input['customer'] ?? '');
        $query = Invoice::listed()->where('status', 'pending');
        if ($request->user()->isStaff()) {
            $query->where('created_by', $request->user()->id);
        }

        $matches = null;
        $invoices = collect();
        if ($customer === '' && $search !== '') {
            $pattern = '%'.strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
            $matches = (clone $query)->whereRaw("customer_name LIKE ? ESCAPE '!'", [$pattern])
                ->selectRaw('MIN(TRIM(customer_name)) AS name')->groupByRaw('LOWER(TRIM(customer_name))')
                ->orderBy('name')->paginate(20)->appends(['q' => $search]);
            if ($matches->total() === 1 && $matches->first()) {
                $customer = $matches->first()->name;
            }
        }
        if ($customer !== '') {
            $invoices = $query->whereRaw('LOWER(TRIM(customer_name)) = LOWER(?)', [$customer])
                ->with(['lineItems', 'payments' => fn ($payments) => $payments->active()])
                ->orderBy('invoice_date')->orderBy('id')->get();
        }
        $totals = $invoices->groupBy('currency')->map(fn ($rows) =>
            $rows->sum(fn ($invoice) => (int) round($invoice->outstandingAmount() * 100)) / 100
        )->sortKeys();

        return view('invoices.customer-balance', compact('search', 'customer', 'matches', 'invoices', 'totals'));
    }
}
