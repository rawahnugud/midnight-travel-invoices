<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\LineItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::with('creator')->with('lineItems');

        if ($user->isStaff()) {
            $query->where('created_by', $user->id);
        }

        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'];
        $invoices = $query->latest()->get()->map(function ($inv) use ($symbols) {
            $sub = $inv->lineItems->sum(fn ($li) => $li->quantity * $li->unit_price);
            $inv->total = round($sub + ($sub * $inv->tax_rate / 100) - $inv->discount_amount, 2);
            $inv->currency_symbol = $symbols[$inv->currency ?? 'USD'] ?? ($inv->currency ?? 'USD') . ' ';
            return $inv;
        });

        return view('invoices.list', ['invoices' => $invoices]);
    }

    public function create()
    {
        $business = BusinessSetting::get();
        $defaultCurrency = $business->default_currency ?? 'USD';
        $nextNumber = 'INV-' . date('Y') . '-' . str_pad((string) (Invoice::max('id') + 1), 3, '0', STR_PAD_LEFT);
        $today = now()->format('Y-m-d');
        return view('invoices.new', [
            'invoice' => (object) [
                'invoice_number' => $nextNumber,
                'invoice_date' => $today,
                'due_date' => $today,
                'currency' => $defaultCurrency,
                'tax_rate' => 0,
                'discount_amount' => 0,
                'items' => [(object) ['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_number' => 'required|string|max:64|unique:invoices,invoice_number',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:64',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending,paid,cancelled',
        ]);

        $validated['created_by'] = $request->user()->id;
        $validated['tax_rate'] = (float) ($validated['tax_rate'] ?? 0);
        $validated['discount_amount'] = (float) ($validated['discount_amount'] ?? 0);
        $validated['status'] = $validated['status'] ?? 'draft';

        $items = $request->input('items', []);
        $hasItem = collect($items)->contains(fn ($row) => ! empty(trim($row['item_name'] ?? '')));
        if (! $hasItem) {
            return back()->withErrors(['items' => __('messages.validation_line_item_required')])->withInput();
        }

        $inv = Invoice::create($validated);
        foreach ($items as $i => $row) {
            if (empty($row['item_name'] ?? null)) {
                continue;
            }
            LineItem::create([
                'invoice_id' => $inv->id,
                'item_name' => $row['item_name'],
                'description' => $row['description'] ?? null,
                'quantity' => (float) ($row['quantity'] ?? 1),
                'unit_price' => (float) ($row['unit_price'] ?? 0),
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('invoices.show', $inv)->with('success', __('messages.invoice_created'));
    }

    public function show(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isViewer() && $invoice->created_by !== $user->id) {
            abort(403);
        }
        $invoice->load('lineItems');
        return view('invoices.view', ['invoice' => $invoice]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }
        $invoice->load('lineItems');
        $invoice->items = $invoice->lineItems->isEmpty() ? [(object) ['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => 0]] : $invoice->lineItems;
        return view('invoices.edit', ['invoice' => $invoice]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }

        $items = $request->input('items', []);
        $hasItem = collect($items)->contains(fn ($row) => ! empty(trim($row['item_name'] ?? '')));
        if (! $hasItem) {
            return back()->withErrors(['items' => __('messages.validation_line_item_required')])->withInput();
        }

        $validated = $request->validate([
            'invoice_number' => ['required', 'string', 'max:64', Rule::unique('invoices', 'invoice_number')->ignore($invoice->id)],
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:64',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending,paid,cancelled',
        ]);

        $validated['tax_rate'] = (float) ($validated['tax_rate'] ?? 0);
        $validated['discount_amount'] = (float) ($validated['discount_amount'] ?? 0);

        DB::transaction(function () use ($invoice, $validated, $items) {
            $invoice->update($validated);
            $invoice->lineItems()->delete();
            foreach ($items as $i => $row) {
                if (empty(trim($row['item_name'] ?? ''))) {
                    continue;
                }
                LineItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $row['item_name'],
                    'description' => $row['description'] ?? null,
                    'quantity' => (float) ($row['quantity'] ?? 1),
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                    'sort_order' => $i,
                ]);
            }
        });

        return redirect()->route('invoices.show', $invoice)->with('success', __('messages.invoice_updated'));
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }
        $invoice->delete();
        return redirect()->route('invoices.index')->with('success', __('messages.invoice_deleted'));
    }

    public function print(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isViewer() && $invoice->created_by !== $user->id) {
            abort(403);
        }
        $invoice->load('lineItems');
        return response()
            ->view('invoices.print', ['invoice' => $invoice])
            ->header('Cache-Control', 'no-store');
    }
}
