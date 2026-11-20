<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class VerifyInvoiceController extends Controller
{
    public function __invoke(Request $request, string $id)
    {
        abort_unless($request->hasValidSignature(false), 404);
        $invoice = Invoice::with('lineItems')->where('branch', 'AE')->findOrFail($id);

        return response()->view('invoices.verify', ['invoice' => $invoice])
            ->header('Cache-Control', 'private, no-store')
            ->header('Referrer-Policy', 'no-referrer')
            ->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
