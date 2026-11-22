<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Invoice::listed()->with(['creator', 'lineItems'])->withExists([
            'payments', 'payments as active_payments_exist' => fn ($query) => $query->active(),
        ]);

        $query->addSelect(['receipt_balance' => Payment::active()->select('balance_after')
            ->whereColumn('payments.invoice_id', 'invoices.id')->latest('id')->limit(1)]);

        if ($user->isStaff()) {
            $query->where('created_by', $user->id);
        }

        $search = trim($request->validate(['q' => 'nullable|string|max:255'])['q'] ?? '');
        if ($search !== '') {
            $pattern = '%' . strtr($search, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            $query->where(fn ($match) => $match
                ->whereRaw("invoice_number LIKE ? ESCAPE '!'", [$pattern])
                ->orWhereRaw("customer_name LIKE ? ESCAPE '!'", [$pattern]));
        }

        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£', 'SDG' => 'SDG'];
        $invoices = $query->latest()->orderByDesc('id')->paginate(20)->appends(['q' => $search]);
        $invoices->getCollection()->transform(function ($inv) use ($symbols) {
            $sub = $inv->lineItems->sum(fn ($li) => $li->quantity * $li->unit_price);
            $inv->total = round($sub + ($sub * $inv->tax_rate / 100) - $inv->discount_amount, 2);
            $inv->currency_symbol = $symbols[$inv->currency ?? 'USD'] ?? ($inv->currency ?? 'USD') . ' ';
            return $inv;
        });

        return view('invoices.list', compact('invoices', 'search'));
    }

    public function create()
    {
        $business = BusinessSetting::get();
        $defaultCurrency = $business->default_currency ?? 'USD';
        $today = now()->format('Y-m-d');
        return view('invoices.new', [
            'invoice' => (object) [
                'invoice_date' => $today,
                'due_date' => $today,
                'currency' => $defaultCurrency,
                'tax_rate' => 0,
                'discount_amount' => 0,
                'items' => [(object) ['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => null]],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedInvoice($request);
        $items = $validated['items'];
        unset($validated['items']);
        $validated['created_by'] = $request->user()->id;
        $validated['status'] ??= Invoice::DEFAULT_STATUS;
        $validated['tax_rate'] ??= 0;
        $validated['discount_amount'] ??= 0;

        $key = $request->validate(['request_key' => 'sometimes|required|uuid'])['request_key'] ?? null;
        $inv = DB::transaction(function () use ($validated, $items, $key) {
            if ($key !== null) {
                $key = strtolower($key);
                $fingerprint = hash('sha256', serialize([$validated, $items]));
                $submission = DB::table('invoice_submissions')->where('request_key', $key);
                $reserved = DB::table('invoice_submissions')->insertOrIgnore([
                    'request_key' => $key, 'fingerprint' => $fingerprint,
                ]);
                if (! $reserved) {
                    $existing = $submission->lockForUpdate()->first();
                    abort_unless($existing && hash_equals($existing->fingerprint, $fingerprint), 409);
                    return Invoice::listed()->where('created_by', $validated['created_by'])->findOrFail($existing->invoice_id);
                }
            }
            // The insert allocates an independent ID for each writer.
            $invoice = Invoice::create($validated + ['invoice_number' => (string) Str::uuid()]);
            $base = sprintf('INV-%s-%03d', now()->year, $invoice->id);
            $number = $base;
            $suffix = 1;
            while (Invoice::where('invoice_number', $number)->exists()) {
                $number = $base . '-' . $suffix++;
            }
            $invoice->update(['invoice_number' => $number]);
            $this->saveItems($invoice, $items);
            if ($key !== null) {
                $submission->update(['invoice_id' => $invoice->id]);
            }
            return $invoice;
        }, 5);

        return redirect()->route('invoices.show', $inv)->with('success', __('messages.invoice_created'));
    }

    private function validatedInvoice(Request $request): array
    {
        return $request->validate([
            'branch' => [
                'nullable',
                Rule::in(Invoice::BRANCHES),
            ],
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'nullable|email',
            'customer_phone' => 'nullable|string|max:64',
            'customer_address' => 'nullable|string',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date',
            'currency' => 'required|string|max:10',
            'tax_rate' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'terms' => 'nullable|string',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:draft,pending,paid,cancelled',
            'items' => 'required|array|min:1',
            'items.*' => 'required|array:item_name,description,quantity,unit_price',
            'items.*.item_name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|decimal:0,2|between:0.01,9999999999.99',
            'items.*.unit_price' => 'required|numeric|decimal:0,2|between:0,9999999999.99',
        ]);
    }

    private function saveItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $position => $item) {
            $invoice->lineItems()->create($item + ['sort_order' => $position]);
        }
    }

    public function show(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if (! $user->isAdmin() && ! $user->isViewer() && $invoice->created_by !== $user->id) {
            abort(403);
        }
        $invoice->load(['lineItems', 'creator', $user->isAdmin() ? 'payments.reversedBy' : 'payments']);
        return view('invoices.view', ['invoice' => $invoice,
            'removed' => DB::table('invoice_removals')->where('invoice_id', $invoice->id)->exists(),
        ]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }
        $this->ensureEditable($invoice);
        $invoice->load('lineItems');
        $invoice->items = $invoice->lineItems->isEmpty() ? [(object) ['item_name' => '', 'description' => '', 'quantity' => 1, 'unit_price' => null]] : $invoice->lineItems;
        return view('invoices.edit', ['invoice' => $invoice]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }

        $validated = $this->validatedInvoice($request);
        $items = $validated['items'];
        unset($validated['items']);
        $validated['tax_rate'] ??= 0;
        $validated['discount_amount'] ??= 0;

        $needsReceipt = DB::transaction(function () use ($invoice, $validated, $items) {
            $invoice = $invoice->lockForPaymentUpdate();
            $this->ensureEditable($invoice);
            $needsReceipt = ($validated['status'] ?? null) === 'paid' && $invoice->status !== 'paid';
            if ($needsReceipt) {
                $validated['status'] = 'pending';
            }
            $invoice->update($validated);
            $invoice->lineItems()->delete();
            $this->saveItems($invoice, $items);
            if ($needsReceipt && $invoice->total <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages(['status' => __('messages.payment_not_allowed')]);
            }
            return $needsReceipt;
        }, 5);

        if ($needsReceipt) {
            return redirect(route('invoices.show', $invoice).'#record-payment')
                ->with('success', __('messages.finish_payment'));
        }
        return redirect()->route('invoices.show', $invoice)->with('success', __('messages.invoice_updated'));
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        if (! $request->user()->isAdmin() && $invoice->created_by !== $request->user()->id) {
            abort(403);
        }
        DB::transaction(function () use ($invoice, $request) {
            $locked = $invoice->lockForPaymentUpdate();
            if (! $locked->payments()->exists()) {
                $locked->delete();
                return;
            }

            abort_unless($locked->status === 'cancelled' && ! $locked->payments()->active()->exists(),
                409, __('messages.invoice_receipts_locked'));
            abort_unless($request->user()->isAdmin(), 403);
            $removals = DB::table('invoice_removals');
            if (! (clone $removals)->where('invoice_id', $locked->id)->exists()) {
                $removals->insert([
                    'invoice_id' => $locked->id,
                    'removed_by' => $request->user()->id,
                    'removed_at' => now(),
                ]);
            }
        }, 5);
        return redirect()->route('invoices.index')->with('success', __('messages.invoice_deleted'));
    }

    public function cancel(Invoice $invoice)
    {
        DB::transaction(function () use ($invoice) {
            $locked = $invoice->lockForPaymentUpdate();
            abort_unless($locked->payments()->exists() && ! $locked->payments()->active()->exists(),
                409, __('messages.cancel_after_reversal_only'));
            if ($locked->status === 'cancelled') {
                return;
            }
            abort_unless(in_array($locked->status, ['pending', 'paid'], true),
                409, __('messages.cancel_after_reversal_only'));
            $locked->update(['status' => 'cancelled']);
        }, 5);

        return redirect()->route('invoices.show', $invoice)->with('success', __('messages.invoice_cancelled'));
    }

    private function ensureEditable(Invoice $invoice): void
    {
        abort_if($invoice->payments()->exists(), 409, __('messages.invoice_receipts_locked'));
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
