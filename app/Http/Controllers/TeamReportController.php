<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TeamReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'currency' => ['nullable', 'string', 'max:10'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'draft', 'cancelled'])],
        ]);

        $issued = Invoice::query();
        $currencies = (clone $issued)->distinct()->orderBy('currency')->pluck('currency');
        $users = User::withTrashed()->orderBy('username')->get(['id', 'username', 'deleted_at']);
        $filtered = $issued
            ->when($filters['from'] ?? null, fn ($q, $from) => $q->where('invoice_date', '>=', $from))
            ->when($filters['to'] ?? null, fn ($q, $to) => $q->where('invoice_date', '<=', $to))
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('created_by', $id))
            ->when($filters['currency'] ?? null, fn ($q, $currency) => $q->where('currency', $currency))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status));

        // Aggregate in the database; only the current drill-down page needs invoice models.
        $amounts = (clone $filtered)->select(['created_by', 'currency', 'status', 'tax_rate', 'discount_amount'])
            ->selectSub(DB::table('line_items')->selectRaw('COALESCE(SUM(quantity * unit_price), 0)')
                ->whereColumn('line_items.invoice_id', 'invoices.id'), 'subtotal')
            ->selectSub(Payment::select('balance_after')->whereColumn('payments.invoice_id', 'invoices.id')
                ->latest('id')->limit(1), 'receipt_balance');

        // Match Invoice::getTotalAttribute(), including separate rounding of the tax.
        $valued = DB::query()->fromSub($amounts, 'amounts')
            ->select(['created_by', 'currency', 'status', 'receipt_balance'])
            ->selectRaw('ROUND(subtotal + ROUND(subtotal * tax_rate / 100.0, 2) - discount_amount, 2) AS invoice_total');

        $rows = DB::query()->fromSub($valued, 'valued')
            ->selectRaw('created_by AS user_id, currency, COUNT(*) AS invoice_count')
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS paid_count")
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count")
            ->selectRaw("ROUND(SUM(CASE WHEN status IN ('pending', 'paid') THEN invoice_total ELSE 0 END), 2) AS invoiced_amount")
            ->selectRaw("ROUND(SUM(CASE WHEN status = 'pending' AND COALESCE(receipt_balance, invoice_total) > 0
                THEN COALESCE(receipt_balance, invoice_total) ELSE 0 END), 2) AS outstanding_amount")
            ->groupBy('created_by', 'currency')->orderBy('currency')->orderBy('created_by')->get();

        $names = $users->pluck('username', 'id');
        foreach ($rows as $row) {
            $row->username = $names->get($row->user_id);
        }
        $rows = $rows->sortBy('username', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $totals = $rows->groupBy('currency')->sortKeys()->map(fn ($group, $currency) => (object) [
            'currency' => $currency,
            'invoice_count' => $group->sum('invoice_count'),
            'paid_count' => $group->sum('paid_count'),
            'pending_count' => $group->sum('pending_count'),
            'invoiced_amount' => round($group->sum('invoiced_amount'), 2),
            'outstanding_amount' => round($group->sum('outstanding_amount'), 2),
        ])->values();

        $invoices = $filtered->with(['creator', 'lineItems', 'payments'])
            ->orderByDesc('invoice_date')->orderByDesc('id')->paginate(25)->withQueryString();

        return response()->view('reports.team', compact('users', 'currencies', 'rows', 'totals', 'invoices'))
            ->header('Cache-Control', 'private, no-store');
    }
}
