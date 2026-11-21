<?php

namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || ($user->isStaff() && $invoice->created_by == $user->id), 403);

        $data = $request->validate([
            'amount' => 'required|numeric|decimal:0,2|between:0.01,9999999999999.99',
            'paid_on' => 'required|date_format:Y-m-d|before_or_equal:today',
            'method' => ['required', Rule::in(Payment::METHODS)],
            'reference' => 'nullable|string|max:255',
            'request_key' => 'required|uuid',
        ]);
        $data['request_key'] = strtolower($data['request_key']);

        try {
            $payment = DB::transaction(function () use ($invoice, $user, $data) {
                $locked = $invoice->lockForPaymentUpdate();
                $previous = Payment::where('request_key', $data['request_key'])->first();
                if ($previous) {
                    return $this->matchingReceipt($previous, $locked, $user->id, $data);
                }

                if ($data['paid_on'] < $locked->invoice_date->format('Y-m-d')) {
                    throw ValidationException::withMessages(['paid_on' => __('messages.payment_before_invoice')]);
                }

                $latest = $locked->latestActivePayment();
                $historical = $locked->status === 'paid' && ! $latest;
                if ($locked->status !== 'pending' && ! $historical) {
                    throw ValidationException::withMessages(['amount' => __('messages.payment_not_allowed')]);
                }

                $due = $latest?->balance_after ?? $locked->total;
                // Receipts use two decimal places, matching the invoice total displayed to the customer.
                if ($due <= 0 || $due > 9999999999999.99) {
                    throw ValidationException::withMessages(['amount' => __('messages.payment_not_allowed')]);
                }
                $dueCents = $this->cents($due);
                $amountCents = $this->cents($data['amount']);
                if ($historical && $amountCents !== $dueCents) {
                    throw ValidationException::withMessages(['amount' => __('messages.payment_historical_full')]);
                }
                if ($amountCents > $dueCents) {
                    throw ValidationException::withMessages(['amount' => __('messages.payment_over_balance')]);
                }

                $balanceCents = $dueCents - $amountCents;
                $payment = $locked->payments()->create([
                    'amount' => $data['amount'],
                    'currency' => $locked->currency,
                    'paid_on' => $data['paid_on'],
                    'method' => $data['method'],
                    'reference' => $data['reference'] ?? null,
                    'balance_after' => sprintf('%d.%02d', intdiv($balanceCents, 100), $balanceCents % 100),
                    'customer_name' => $locked->customer_name,
                    'issuer_name' => $locked->issuerName(BusinessSetting::get()),
                    'invoice_number' => $locked->invoice_number,
                    'branch' => $locked->branch,
                    'request_key' => $data['request_key'],
                    'issued_by' => $user->id,
                    'prior_invoice_status' => $locked->status,
                ]);
                if ($balanceCents === 0 && ! $historical) {
                    $locked->update(['status' => 'paid']);
                }

                return $payment;
            }, 5);
        } catch (QueryException $exception) {
            // Concurrent requests for different invoices can race for the same globally unique key.
            $previous = Payment::where('request_key', $data['request_key'])->first();
            if (! $previous) {
                throw $exception;
            }
            $payment = $this->matchingReceipt($previous, $invoice, $user->id, $data);
        }

        return redirect()->route('invoices.payments.show', [$invoice, $payment]);
    }

    public function reverse(Request $request, Invoice $invoice, Payment $payment)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:500'],
            'prior_status' => ['nullable', Rule::in(['pending', 'paid'])],
        ]);

        DB::transaction(function () use ($invoice, $payment, $request, $data) {
            $locked = $invoice->lockForPaymentUpdate();
            $receipt = $locked->payments()->findOrFail($payment->id);
            if ($receipt->reversed_at !== null) {
                return;
            }
            if ($locked->latestActivePayment()?->id !== $receipt->id) {
                throw ValidationException::withMessages(['reason' => __('messages.reverse_latest_only')]);
            }

            $priorStatus = $receipt->prior_invoice_status ?? 'pending';
            if ($receipt->requiresPriorStatus()) {
                if (empty($data['prior_status'])) {
                    throw ValidationException::withMessages(['prior_status' => __('messages.choose_prior_status')]);
                }
                $priorStatus = $data['prior_status'];
            }
            $receipt->prior_invoice_status = $priorStatus;
            $receipt->reversed_at = now();
            $receipt->reversed_by = $request->user()->id;
            $receipt->reversal_reason = trim($data['reason']);
            $receipt->save();
            $locked->update(['status' => $priorStatus]);
        }, 5);

        return redirect()->route('invoices.show', $invoice)->with('success', __('messages.receipt_reversed_success'));
    }

    private function cents($amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function matchingReceipt(Payment $payment, Invoice $invoice, int $userId, array $data): Payment
    {
        if ($payment->invoice_id != $invoice->id || $payment->issued_by != $userId
            || $this->cents($payment->amount) !== $this->cents($data['amount'])
            || $payment->paid_on->format('Y-m-d') !== $data['paid_on']
            || $payment->method !== $data['method']
            || $payment->reference !== ($data['reference'] ?? null)) {
            throw ValidationException::withMessages(['request_key' => __('messages.payment_key_conflict')]);
        }

        return $payment;
    }

    public function show(Request $request, Invoice $invoice, Payment $payment)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isViewer() || ($user->isStaff() && $invoice->created_by == $user->id), 403);

        return response()->view('invoices.receipt', [
            'invoice' => $invoice,
            'payment' => $payment,
            'business' => BusinessSetting::get(),
        ])->header('Cache-Control', 'private, no-store');
    }
}
