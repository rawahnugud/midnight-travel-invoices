<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Str;

class InvoiceWorkflowTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        $app->instance('env', 'testing');
        $app['config']->set([
            'app.env' => 'testing',
            'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null,
            'session.driver' => 'array',
            'cache.default' => 'array',
            'cache.stores.array' => ['driver' => 'array'],
        ]);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $connection = $this->app['db']->connection();
        $this->assertSame('sqlite', $connection->getDriverName());
        $this->assertSame('', $connection->selectOne('PRAGMA database_list')->file);
        $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_design_settings_can_replace_the_browser_icon(): void
    {
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $settings = \App\Models\BusinessSetting::get();
        $settings->update(['login_logo_path' => 'business/example-login.png']);
        \App\Models\BusinessSetting::clearCache();
        $this->get(route('dashboard'))->assertSee('rel="icon" href="'.asset('business/example-login.png').'"', false);
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r0kAAAAASUVORK5CYII=');
        $paths = [];
        try {
            foreach ([1, 2] as $upload) {
                $this->put(route('settings.design.update'), [
                    'browser_icon' => \Illuminate\Http\UploadedFile::fake()->createWithContent('icon.png', $png),
                ])->assertRedirect(route('settings.design.edit'))->assertSessionHasNoErrors();
                $settings->refresh();
                $this->assertNotEmpty($settings->browser_icon_path);
                $paths[] = public_path($settings->browser_icon_path);
                $this->assertFileExists(end($paths));
                $this->get(route('dashboard'))->assertSee('rel="icon" href="'.$settings->browser_icon_url.'"', false);
            }
            $this->assertFileDoesNotExist($paths[0]);
            $this->assertSame('business/example-login.png', $settings->login_logo_path);
            $this->put(route('settings.design.update'), [
                'browser_icon' => \Illuminate\Http\UploadedFile::fake()->createWithContent('icon.png', 'invalid'),
            ])->assertSessionHasErrors('browser_icon');
            $this->assertFileExists($paths[1]);
        } finally {
            \Illuminate\Support\Facades\File::delete($paths);
        }
    }

    public function test_customer_balance_separates_names_currencies_and_staff_visibility(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pending = Invoice::where('status', 'pending')->sole();
        $staff = $pending->creator;
        $pending->update(['customer_name' => 'Example Group', 'currency' => 'USD']);
        $this->actingAs($admin);
        $add = function (string $name, string $currency) {
            $this->post(route('invoices.store'), [
                'customer_name' => $name, 'currency' => $currency, 'invoice_date' => today()->toDateString(),
                'items' => [['item_name' => 'Ticket', 'quantity' => 1, 'unit_price' => '25.00']],
            ])->assertSessionHasNoErrors();
        };
        $add('Example Group', 'AED');
        $add('Example Group East', 'USD');
        $paid = Invoice::where('status', 'paid')->sole();
        $paid->update(['customer_name' => 'Example Group']);
        $this->post(route('invoices.payments.store', $pending), $this->paymentPayload('10.00'))->assertSessionHasNoErrors();
        $lookup = fn ($params) => $this->get(route('invoices.customer-balance', $params))->assertOk();
        $lookup(['q' => 'Example'])->assertSeeText('Example Group East')->assertDontSeeText($pending->invoice_number);
        $report = $lookup(['customer' => ' example group '])->assertSeeText($pending->invoice_number)->assertDontSeeText($paid->invoice_number);
        $this->assertCount(2, $report->viewData('invoices'));
        $this->assertEquals(['AED' => 25, 'USD' => 830], $report->viewData('totals')->all());
        $this->post(route('invoices.payments.reverse', [$pending, $pending->payments()->sole()]), ['reason' => 'Correction'])->assertSessionHasNoErrors();
        $this->actingAs($staff);
        $this->assertEquals(['USD' => 840], $lookup(['q' => 'Example'])->viewData('totals')->all());
        $lookup(['customer' => 'Example Group East'])->assertSeeText(__('messages.no_pending_customer'));
    }

    public function test_new_letters_prefill_the_sender_in_the_selected_language(): void
    {
        config(['bank_letters.ar' => ['holder' => 'شركة تجريبية', 'signer' => 'اسم تجريبي',
            'role' => 'المدير العام', 'account' => '000123']]);
        $this->actingAs(User::where('role', 'admin')->firstOrFail())->withSession(['locale' => 'ar'])
            ->get(route('letters.create'))->assertOk()->assertSee('value="000123"', false)
            ->assertSee('value="شركة تجريبية"', false)->assertSee('value="اسم تجريبي"', false)
            ->assertSee('value="المدير العام"', false);
    }

    public function test_bank_letter_drafts_can_be_amended_copied_and_printed_by_admin(): void
    {
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $draft = ['language' => 'en', 'date' => '2026-11-13', 'rows' => [['name' => 'Example']]];
        $this->post(route('letters.store'), $draft)->assertSessionHasNoErrors();
        $letter = \App\Models\BankLetter::sole();
        $this->get(route('letters.print', $letter))->assertRedirect(route('letters.edit', $letter));
        $draft += ['bank' => 'Example Bank', 'holder' => 'Example Agency', 'account' => '000123',
            'signer' => 'Example Signer', 'role' => 'Manager', 'action' => 'print'];
        $draft['rows'] = [['name' => 'A', 'account' => '0012', 'amount' => '55079933.10'],
            ['name' => 'B', 'account' => '0034', 'amount' => '75900162.95']];
        $this->put(route('letters.update', $letter), $draft + ['revision' => 1])->assertRedirect(route('letters.print', $letter));
        $this->put(route('letters.update', $letter), $draft + ['revision' => 1])->assertStatus(409);
        $this->get(route('letters.print', $letter))->assertOk()->assertSeeText('130,980,096.05')
            ->assertSeeText('one hundred and thirty million')->assertSeeText('five piastres')->assertSeeText('0012');
        $this->get(route('letters.copy', $letter))->assertOk()->assertSee('0012');
        $draft['language'] = 'ar';
        $this->post(route('letters.store'), $draft)->assertSessionHasNoErrors();
        $copy = \App\Models\BankLetter::latest('id')->first();
        $this->assertNotEquals($letter->id, $copy->id);
        $this->assertSame('en', $letter->fresh()->details['language']);
        $this->get(route('letters.print', $copy))->assertOk()->assertSee('dir="rtl"', false)
            ->assertSeeText('طلب تحويل داخلي')->assertSeeText('خمسة قروش');
        $this->actingAs(User::where('role', 'staff')->firstOrFail());
        foreach (['edit','copy','print'] as $page) {
            $this->get(route('letters.'.$page, $letter))->assertForbidden();
        }
        $this->get(route('letters.index'))->assertForbidden();
        $this->post(route('letters.store'), $draft)->assertForbidden();
        $this->put(route('letters.update', $letter), $draft)->assertForbidden();
    }

    public function test_branch_artwork_on_printed_invoices(): void
    {
        $settings = \App\Models\BusinessSetting::get();
        $settings->update(['company_name' => 'Original issuer', 'address' => 'Port Sudan office', 'logo_path' => 'business/group.png', 'stamp_path' => 'business/stamp.png']);
        \App\Models\BusinessSetting::clearCache();
        $admin = User::where('role', 'admin')->firstOrFail();
        $invoice = Invoice::firstOrFail();
        $print = route('invoices.print', $invoice);
        $settingsUrl = route('settings.branch.update', 'AE');
        $this->actingAs($admin)->get(route('settings.business.edit'))->assertOk()->assertSee($settingsUrl);
        $this->get($print)->assertOk()->assertSee('business/stamp.png')->assertSee('business/group.png')
            ->assertSee('Original issuer')->assertSee('Port Sudan office')->assertDontSee(__('messages.branch_SD'));
        $invoice->update(['branch' => 'AE']);
        $this->get($print)->assertOk()->assertSee('business/group.png')->assertDontSee('business/stamp.png');
        $this->put($settingsUrl, ['logo_mode' => 'shared', 'stamp_mode' => 'shared'])->assertSessionHasNoErrors();
        $this->get($print)->assertOk()->assertSee('business/stamp.png');
        $this->put($settingsUrl, ['logo_mode' => 'custom', 'stamp_mode' => 'none'])->assertSessionHasErrors('logo');

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/r0kAAAAASUVORK5CYII=');
        $this->put($settingsUrl, [
            'logo_mode' => 'custom', 'stamp_mode' => 'none',
            'logo' => \Illuminate\Http\UploadedFile::fake()->createWithContent('branch.png', $png),
        ])->assertSessionHasNoErrors();
        $logo = $settings->fresh()->invoiceLogoPath('AE');
        try {
            $this->assertStringStartsWith('business/AE_logo_', $logo);
            $this->assertFileExists(public_path($logo));
            $this->get($print)->assertOk()->assertSee($logo)->assertDontSee('business/stamp.png');
            $invoice->update(['branch' => 'SD']);
            $this->get($print)->assertOk()->assertSee('business/group.png')->assertDontSee($logo);
            $this->actingAs(User::where('role', 'staff')->firstOrFail())->put($settingsUrl, [])->assertForbidden();
        } finally {
            \Illuminate\Support\Facades\File::delete(public_path($logo));
        }
    }

    public function test_branch_details_and_tax_numbers_are_isolated_on_invoices(): void
    {
        $this->actingAs(User::where('role', 'admin')->firstOrFail());
        $this->put(route('settings.business.update'), [
            'company_name' => 'Default Sudan', 'tax_id' => '001-SD',
        ])->assertSessionHasNoErrors();
        $this->get(route('settings.business.edit'))->assertOk()
            ->assertSee('id="AE-tax_id"', false)->assertSee('id="GB-tax_id"', false)
            ->assertDontSeeText(__('messages.branch_SD'));
        $branding = ['logo_mode' => 'shared', 'stamp_mode' => 'none'];
        $invoice = Invoice::firstOrFail();
        $print = route('invoices.print', $invoice);
        foreach (['AE' => 'AED', 'GB' => 'GBP'] as $branch => $currency) {
            $invoice->update(['branch' => $branch]);
            $this->get($print)->assertOk()->assertDontSeeText('001-SD');
            $this->put(route('settings.branch.update', $branch), $branding + [
                'details' => ['company_name' => 'Issuer '.$branch, 'address' => 'Office '.$branch,
                    'default_currency' => $currency, 'tax_id' => '002-'.$branch],
            ])->assertSessionHasNoErrors();
        }
        foreach (['AE', 'GB'] as $branch) {
            $invoice->update(['branch' => $branch]);
            foreach (['show', 'print'] as $page) {
                $this->get(route('invoices.'.$page, $invoice))->assertOk()
                    ->assertSeeText('Issuer '.$branch)->assertSeeText('Office '.$branch)
                    ->assertSeeText('002-'.$branch)->assertDontSeeText('001-SD')
                    ->assertDontSeeText($branch === 'GB' ? '002-AE' : '002-GB');
            }
        }
        foreach (['SD', null] as $branch) {
            $invoice->update(['branch' => $branch]);
            $this->get($print)->assertOk()->assertSeeText('001-SD')->assertDontSeeText('002-AE');
        }
        $this->get(route('invoices.create'))->assertOk()->assertSee('data-currency="AED"', false);
        $this->put(route('settings.branch.update', 'AE'), $branding + ['details' => ['tax_id' => '']])
            ->assertSessionHasNoErrors();
        $invoice->update(['branch' => 'AE']);
        $this->get($print)->assertOk()->assertDontSeeText('002-AE')->assertDontSeeText('001-SD');
    }

    public function test_guest_can_verify_signed_invoices_for_every_issuer(): void
    {
        $invoice = Invoice::firstOrFail();
        $invoice->update(['branch' => 'AE']);
        $link = \Illuminate\Support\Facades\URL::signedRoute('invoice.verify', ['id' => $invoice->id], null, false);
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin)->get(route('invoices.print', $invoice))
            ->assertOk()->assertSee(url($link))->assertSee('verification-qr');
        auth()->logout();
        $this->get($link)->assertOk()->assertSee($invoice->invoice_number)
            ->assertSee($invoice->currency.' '.number_format($invoice->total, 2))
            ->assertDontSee($invoice->customer_name);
        $this->get(route('invoice.verify', ['id' => $invoice->id]))->assertNotFound();
        $this->get($link.'0')->assertNotFound();
        $invoice->update(['status' => 'cancelled']);
        $this->get($link)->assertOk()->assertSee(__('messages.cancelled'));
        foreach (['SD', 'GB', null] as $branch) {
            $invoice->update(['branch' => $branch]);
            $issuer = $branch ? __('messages.branch_'.$branch) : \App\Models\BusinessSetting::get()->company_name;
            $this->get($link)->assertOk()->assertSee($issuer)->assertDontSee($invoice->customer_name);
            $this->actingAs($admin)->get(route('invoices.print', $invoice))
                ->assertOk()->assertSee(url($link))->assertSee('verification-qr');
            auth()->logout();
        }
    }

    private function paymentPayload(string $amount): array
    {
        return [
            'request_key' => (string) Str::uuid(),
            'amount' => $amount,
            'paid_on' => today()->toDateString(),
            'method' => 'bank',
            'reference' => 'TRANSFER-123',
        ];
    }

    public function test_customer_print_omits_payment_status_and_staff_list_tracks_balance(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $this->actingAs($staff)->get(route('invoices.create'))->assertOk()
            ->assertDontSee('name="status" value="draft"', false);
        $this->post(route('invoices.store'), [
            'customer_name' => 'Invoice status fixture',
            'invoice_date' => today()->toDateString(),
            'currency' => 'USD',
            'items' => [['item_name' => 'Ticket', 'quantity' => 1, 'unit_price' => '100.00']],
        ])->assertSessionHasNoErrors();
        $invoice = Invoice::where('customer_name', 'Invoice status fixture')->sole();
        $this->assertSame('pending', $invoice->status);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSeeText(__('messages.pending'));
        $this->get(route('invoices.print', $invoice))->assertOk()->assertDontSeeText(__('messages.pending'));
        $store = route('invoices.payments.store', $invoice);
        $this->post($store, $this->paymentPayload('40.00'))->assertSessionHasNoErrors();
        $this->get(route('invoices.index', ['q' => $invoice->invoice_number]))->assertOk()
            ->assertSeeText('USD 60.00')->assertSeeText(__('messages.pending'));
        $this->get(route('invoices.print', $invoice))->assertOk()->assertDontSeeText(__('messages.payment_status'));
        $this->post($store, $this->paymentPayload('60.00'))->assertSessionHasNoErrors();
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSeeText(__('messages.paid'));
        $this->get(route('invoices.print', $invoice))->assertOk()->assertDontSeeText(__('messages.paid'));
    }

    public function test_team_report_requires_an_admin(): void
    {
        $report = route('reports.team');
        $this->get($report)->assertRedirect(route('login'));
        $staff = User::where('role', 'staff')->firstOrFail();
        $this->actingAs($staff)->get($report)->assertForbidden();
        $staff->update(['role' => 'viewer']);
        $this->actingAs($staff)->get($report)->assertForbidden();
        $this->actingAs(User::where('role', 'admin')->firstOrFail())->get($report)->assertOk();
    }

    public function test_team_report_reconciles_partial_and_historical_payments_by_issuer_and_currency(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pending = Invoice::where('status', 'pending')->sole();
        $historical = Invoice::where('status', 'paid')->sole();
        $historical->update(['currency' => 'GBP']);
        $staff = $pending->creator;
        $this->actingAs($admin);
        $store = route('invoices.payments.store', $pending);
        $this->post($store, $this->paymentPayload('100.00'))->assertSessionHasNoErrors();
        $this->post($store, $this->paymentPayload('100.25'))->assertSessionHasNoErrors();
        $staff->delete();

        $report = $this->get(route('reports.team'))->assertOk()
            ->assertSeeText($historical->invoice_number)->assertSeeText($pending->invoice_number);
        $totals = collect($report->viewData('totals'))->keyBy('currency');
        $this->assertCount(2, $totals);
        $this->assertEquals(1, data_get($totals['USD'], 'pending_count'));
        $this->assertEquals(840, data_get($totals['USD'], 'invoiced_amount'));
        $this->assertEquals(639.75, data_get($totals['USD'], 'outstanding_amount'));
        $this->assertEquals(1, data_get($totals['GBP'], 'paid_count'));
        $this->assertEquals(0, data_get($totals['GBP'], 'outstanding_amount'));

        $filters = [
            'user_id' => $staff->id,
            'from' => today()->toDateString(),
            'to' => today()->toDateString(),
            'currency' => 'USD',
            'status' => 'pending',
        ];
        $filtered = $this->get(route('reports.team', $filters))->assertOk()
            ->assertSeeText($pending->invoice_number)->assertDontSeeText($historical->invoice_number);
        $this->assertEquals(1, $filtered->viewData('invoices')->total());
        foreach (['user_id' => $admin->id, 'currency' => 'GBP', 'status' => 'paid',
                  'from' => today()->addDay()->toDateString(), 'to' => today()->subDay()->toDateString()] as $key => $value) {
            $other = $filters;
            if ($key === 'to') {
                unset($other['from']);
            } elseif ($key === 'from') {
                unset($other['to']);
            }
            $other[$key] = $value;
            $this->get(route('reports.team', $other))->assertOk()->assertDontSeeText($pending->invoice_number);
        }
    }

    public function test_partial_payment_retry_and_final_payment_keep_the_correct_balance(): void
    {
        $invoice = Invoice::where('status', 'pending')->firstOrFail();
        $this->actingAs($invoice->creator);
        $store = route('invoices.payments.store', $invoice);
        $partial = $this->paymentPayload('200.25');
        $this->post($store, $partial)->assertSessionHasNoErrors()->assertRedirect();
        $payment = Payment::where('invoice_id', $invoice->id)->sole();
        $this->assertEquals('200.25', $payment->amount);
        $this->assertEquals('639.75', $payment->balance_after);
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->post($store, $partial)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($payment->id, Payment::sole()->id);
        $this->get(route('invoices.edit', $invoice))->assertStatus(409);
        $this->delete(route('invoices.destroy', $invoice))->assertStatus(409);
        $this->get(route('invoices.show', $invoice))->assertOk()->assertSee($payment->receiptNumber());

        $this->post($store, $this->paymentPayload('640.00'))->assertSessionHasErrors();
        $this->assertSame(1, Payment::count());
        $final = $this->paymentPayload('639.75');
        $this->post($store, $final)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($store, $final)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, Payment::count());
        $this->assertEquals(0, Payment::where('request_key', $final['request_key'])->sole()->balance_after);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_historical_paid_invoice_can_only_receive_one_full_settlement_receipt(): void
    {
        $invoice = Invoice::where('status', 'paid')->firstOrFail();
        $this->actingAs($invoice->creator);
        $store = route('invoices.payments.store', $invoice);
        $this->post($store, $this->paymentPayload('1.00'))->assertSessionHasErrors();
        $this->assertSame(0, Payment::count());
        $this->post($store, $this->paymentPayload('345.00'))->assertSessionHasNoErrors()->assertRedirect();
        $this->post($store, $this->paymentPayload('345.00'))->assertSessionHasErrors();
        $this->assertEquals(0, Payment::sole()->balance_after);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->get(route('dashboard'))->assertOk()->assertSee('USD 345.00');
    }

    public function test_reversing_receipts_restores_balances_and_keeps_numbered_history(): void
    {
        $invoice = Invoice::where('status', 'pending')->sole();
        $this->actingAs(User::where('role', 'admin')->sole());
        $store = route('invoices.payments.store', $invoice);
        $this->post($store, $this->paymentPayload('200.25'))->assertSessionHasNoErrors();
        $first = $invoice->payments()->sole();
        $finalPayload = $this->paymentPayload('639.75');
        $this->post($store, $finalPayload)->assertSessionHasNoErrors();
        $final = $invoice->payments()->latest('id')->firstOrFail();
        $reverseFirst = route('invoices.payments.reverse', [$invoice, $first]);
        $reverseFinal = route('invoices.payments.reverse', [$invoice, $final]);
        $reason = ['reason' => 'Bank transfer entered on the wrong invoice'];
        $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertSee($reverseFinal)->assertDontSee($reverseFirst);
        $this->post($reverseFirst, $reason)->assertSessionHasErrors();
        $this->assertSame('paid', $invoice->fresh()->status);

        $this->post($reverseFinal, $reason)->assertSessionHasNoErrors();
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertEquals(639.75, $invoice->fresh()->outstandingAmount());
        $this->assertEquals(639.75, $invoice->fresh(['payments'])->outstandingAmount());
        $report = $this->get(route('reports.team', ['user_id' => $invoice->created_by]))->assertOk();
        $this->assertEquals(639.75, $report->viewData('totals')->sole()->outstanding_amount);
        $this->assertSame($reason['reason'], $final->fresh()->reversal_reason);
        $this->assertEquals(auth()->id(), $final->fresh()->reversed_by);
        $this->assertNotNull($final->fresh()->reversed_at);
        $this->assertSame($final->receiptNumber(), $final->fresh()->receiptNumber());
        $this->get(route('invoices.payments.show', [$invoice, $final]))->assertOk()
            ->assertSeeText(__('messages.reversed_receipt_notice'))->assertDontSeeText($reason['reason']);
        $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertSeeText($reason['reason'])->assertSee(route('invoices.payments.store', $invoice));
        $this->post($reverseFinal, $reason)->assertSessionHasNoErrors();
        $this->post($store, $finalPayload);
        $this->assertEquals(639.75, $invoice->fresh()->outstandingAmount());
        $this->assertSame(2, $invoice->payments()->count());

        $this->post($reverseFirst, $reason)->assertSessionHasNoErrors();
        $this->assertEquals(840, $invoice->fresh()->outstandingAmount());
        $this->post($store, $this->paymentPayload('840.00'))->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertSame(3, $invoice->payments()->count());
        $this->assertSame(2, $invoice->payments()->whereNotNull('reversed_at')->count());
    }

    public function test_receipt_reversal_requires_admin_matching_invoice_and_reason(): void
    {
        $invoice = Invoice::where('status', 'pending')->sole();
        $staff = $invoice->creator;
        $this->actingAs($staff)->post(route('invoices.payments.store', $invoice), $this->paymentPayload('100.00'))
            ->assertSessionHasNoErrors();
        $payment = $invoice->payments()->sole();
        $route = route('invoices.payments.reverse', [$invoice, $payment]);
        $reason = ['reason' => 'Incorrect amount'];
        $this->post($route, $reason)->assertForbidden();
        $staff->update(['role' => 'viewer']);
        $this->actingAs($staff)->post($route, $reason)->assertForbidden();
        $this->actingAs(User::where('role', 'admin')->sole());
        $other = Invoice::whereKeyNot($invoice->id)->firstOrFail();
        $this->post(route('invoices.payments.reverse', [$other, $payment]), $reason)->assertNotFound();
        $this->post($route)->assertSessionHasErrors('reason');
        $this->assertNull($payment->fresh()->reversed_at);
        $this->assertEquals(740, $invoice->fresh()->outstandingAmount());
    }

    public function test_replacing_a_receipt_for_a_previously_paid_invoice_keeps_it_settled(): void
    {
        $invoice = Invoice::where('status', 'paid')->sole();
        $this->actingAs(User::where('role', 'admin')->sole());
        $store = route('invoices.payments.store', $invoice);
        $this->post($store, $this->paymentPayload('345.00'))->assertSessionHasNoErrors();
        $receipt = $invoice->payments()->sole();
        $this->post(route('invoices.payments.reverse', [$invoice, $receipt]), ['reason' => 'Incorrect method'])
            ->assertSessionHasNoErrors();
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->assertEquals(0, $invoice->fresh()->outstandingAmount());
        $this->post($store, $this->paymentPayload('300.00'))->assertSessionHasErrors();
        $this->post($store, $this->paymentPayload('345.00'))->assertSessionHasNoErrors();
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_ambiguous_legacy_full_receipt_requires_prior_status_before_reversal(): void
    {
        $invoice = Invoice::where('status', 'pending')->sole();
        $this->actingAs(User::where('role', 'admin')->sole());
        $this->post(route('invoices.payments.store', $invoice), $this->paymentPayload('840.00'))
            ->assertSessionHasNoErrors();
        $payment = $invoice->payments()->sole();
        $payment->forceFill(['prior_invoice_status' => null])->save();
        $reverse = route('invoices.payments.reverse', [$invoice, $payment]);
        $reason = ['reason' => 'Incorrect settlement'];
        $this->post($reverse, $reason)->assertSessionHasErrors('prior_status');
        $this->assertNull($payment->fresh()->reversed_at);
        $this->post($reverse, $reason + ['prior_status' => 'pending'])->assertSessionHasNoErrors();
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertEquals(840, $invoice->fresh()->outstandingAmount());
    }

    public function test_admin_can_cancel_an_invoice_after_all_receipts_have_been_reversed(): void
    {
        $invoice = Invoice::where('status', 'pending')->sole();
        $admin = User::where('role', 'admin')->sole();
        $cancel = route('invoices.cancel', $invoice);
        $this->actingAs($admin)->post($cancel)->assertStatus(409);
        $this->post(route('invoices.payments.store', $invoice), $this->paymentPayload('840.00'))
            ->assertSessionHasNoErrors();
        $receipt = $invoice->payments()->sole();
        $this->post($cancel)->assertStatus(409);
        $this->assertSame('paid', $invoice->fresh()->status);
        $this->post(route('invoices.payments.reverse', [$invoice, $receipt]), ['reason' => 'Wrong invoice'])
            ->assertSessionHasNoErrors();
        $this->actingAs($invoice->creator)->post($cancel)->assertForbidden();
        $this->actingAs($admin)->post($cancel)->assertRedirect(route('invoices.show', $invoice));
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->get(route('invoices.print', $invoice))->assertOk()->assertSeeText(__('messages.cancelled'));
        $this->assertSame($receipt->id, $invoice->payments()->sole()->id);
        $this->actingAs($invoice->creator)->delete(route('invoices.destroy', $invoice))->assertForbidden();
        $this->actingAs($admin)->get(route('invoices.index'))->assertOk()
            ->assertSee('action="'.route('invoices.destroy', $invoice).'"', false);
        $this->get(route('invoices.show', $invoice))->assertOk()
            ->assertSee('action="'.route('invoices.destroy', $invoice).'"', false);
        $this->delete(route('invoices.destroy', $invoice))->assertRedirect(route('invoices.index'));
        $this->get(route('invoices.index'))->assertOk()->assertDontSeeText($invoice->invoice_number);
        $this->get(route('reports.team'))->assertOk()->assertDontSeeText($invoice->invoice_number);
        $this->get(route('dashboard'))->assertOk()->assertDontSeeText($invoice->invoice_number);
        $this->delete(route('invoices.destroy', $invoice))->assertRedirect(route('invoices.index'));
        $this->assertDatabaseHas('invoice_removals', ['invoice_id' => $invoice->id, 'removed_by' => $admin->id]);
        $this->get(route('invoices.payments.show', [$invoice, $receipt]))->assertOk();
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->post(route('invoices.payments.store', $invoice), $this->paymentPayload('840.00'))
            ->assertSessionHasErrors();
    }

    public function test_draft_and_cancelled_invoices_reject_payment(): void
    {
        $invoice = Invoice::where('status', 'pending')->firstOrFail();
        $this->actingAs($invoice->creator);
        foreach (['draft', 'cancelled'] as $status) {
            $invoice->update(['status' => $status]);
            $this->post(route('invoices.payments.store', $invoice), $this->paymentPayload('1.00'))
                ->assertSessionHasErrors();
            $this->assertSame($status, $invoice->fresh()->status);
        }
        $this->assertSame(0, Payment::count());
    }

    public function test_receipt_access_matches_invoice_permissions_and_print_uses_issued_details(): void
    {
        $invoice = Invoice::where('status', 'paid')->firstOrFail();
        $staff = User::where('role', 'staff')->firstOrFail();
        $store = route('invoices.payments.store', $invoice);
        $paymentForm = $this->paymentPayload('345.00');
        $this->actingAs($staff)->post($store, $paymentForm)->assertForbidden();
        $this->actingAs($invoice->creator)->post($store, $paymentForm)->assertSessionHasNoErrors();
        $payment = Payment::sole();
        $receipt = route('invoices.payments.show', [$invoice, $payment]);
        $this->assertSame($invoice->customer_name, $payment->customer_name);
        $this->assertNotEmpty($payment->issuer_name);
        $invoice->update(['customer_name' => 'Renamed customer', 'branch' => 'GB']);
        $this->get($receipt)->assertOk()->assertSee($payment->customer_name)->assertSee($payment->issuer_name)
            ->assertSee('345.00')->assertSee('TRANSFER-123')->assertDontSee('Renamed customer');
        $other = Invoice::whereKeyNot($invoice->id)->firstOrFail();
        $this->get(route('invoices.payments.show', [$other, $payment]))->assertNotFound();
        $this->actingAs($staff)->get($receipt)->assertForbidden();
        $staff->update(['role' => 'viewer']);
        $this->actingAs($staff)->get($receipt)->assertOk();
        $this->post($store, $paymentForm)->assertForbidden();
        $this->assertSame(1, Payment::count());
        auth()->logout();
        $this->get($receipt)->assertRedirect(route('login'));
    }

    public function test_admin_can_deactivate_staff_without_losing_invoice_history(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $staff = User::where('role', 'staff')->firstOrFail();
        $invoice = Invoice::where('created_by', $staff->id)->firstOrFail();
        $items = $invoice->lineItems()->pluck('id')->all();
        $this->assertNotEmpty($items);

        $this->actingAs($admin)->delete(route('users.destroy', $staff))
            ->assertRedirect(route('users.index'));

        $this->assertSoftDeleted($staff);
        $invoice->refresh();
        $this->assertTrue($staff->is($invoice->creator));
        $this->assertSame($items, $invoice->lineItems()->pluck('id')->all());
        $provider = auth()->guard()->getProvider();
        $this->assertNull($provider->retrieveById($staff->id));
        $this->assertNull($provider->retrieveByCredentials(['username' => $staff->username]));
    }

    public function test_viewers_can_read_but_cannot_change_invoices(): void
    {
        $staff = User::where('role', 'staff')->firstOrFail();
        $invoice = $staff->invoices()->firstOrFail();
        $staff->update(['role' => 'viewer']);
        $this->actingAs($staff)->get(route('invoices.show', $invoice))->assertOk();
        $this->get(route('invoices.print', $invoice))->assertOk();
        $this->get(route('invoices.create'))->assertForbidden();
        $this->post(route('invoices.store'))->assertForbidden();
        $this->put(route('invoices.update', $invoice))->assertForbidden();
        $this->delete(route('invoices.destroy', $invoice))->assertForbidden();
    }

    public function test_pagination_uses_plain_localized_labels_and_preserves_links(): void
    {
        foreach (['en' => ['Previous', 'Next'], 'ar' => ['السابق', 'التالي']] as $locale => $labels) {
            app()->setLocale($locale);
            foreach ([1, 2, 10] as $page) {
                $pages = new \Illuminate\Pagination\LengthAwarePaginator([], 200, 20, $page, ['path' => '/invoices']);
                $html = $pages->appends(['q' => 'ticket'])->links('pagination.default')->render();
                $this->assertStringContainsString($labels[0], $html);
                $this->assertStringContainsString($labels[1], $html);
                $this->assertStringNotContainsString('&amp;laquo;', $html);
                $this->assertStringNotContainsString('&amp;raquo;', $html);
                $this->assertSame($page > 1, str_contains($html, 'rel="prev"'));
                $this->assertSame($page < 10, str_contains($html, 'rel="next"'));
                $this->assertStringContainsString('q=ticket', $html);
            }
        }
    }

    public function test_pending_filters_follow_active_receipts_and_respect_search_and_staff_visibility(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $pending = Invoice::where('status', 'pending')->sole();
        $paid = Invoice::where('status', 'paid')->sole();
        $this->actingAs($admin);
        $list = fn ($filter, $q = '') => $this->get(route('invoices.index', compact('filter', 'q')))->assertOk();
        $list('unpaid')->assertSeeText($pending->invoice_number)->assertDontSeeText($paid->invoice_number);
        $this->post(route('invoices.payments.store', $pending), $this->paymentPayload('10.00'))->assertSessionHasNoErrors();
        $list('pending')->assertSeeText($pending->invoice_number);
        $list('partially_paid', $pending->customer_name)->assertSeeText($pending->invoice_number);
        $list('partially_paid', 'no matching customer')->assertSeeText(__('reports.no_matches'));
        $list('unpaid')->assertDontSeeText($pending->invoice_number);
        $this->post(route('invoices.payments.reverse', [$pending, $pending->payments()->sole()]), ['reason' => 'Correction'])
            ->assertSessionHasNoErrors();
        $list('unpaid')->assertSeeText($pending->invoice_number);
        $list('partially_paid')->assertDontSeeText($pending->invoice_number);
        $this->actingAs($pending->creator);
        $list('paid')->assertDontSeeText($paid->invoice_number);
        foreach (['draft', 'cancelled'] as $status) {
            $pending->update(['status' => $status]);
            $list($status)->assertSeeText($pending->invoice_number);
            $list('pending')->assertDontSeeText($pending->invoice_number);
        }
        $pages = $list('cancelled', $pending->customer_name)->viewData('invoices');
        parse_str(parse_url($pages->url(2), PHP_URL_QUERY), $params);
        $this->assertSame('cancelled', $params['filter']);
        $this->assertSame($pending->customer_name, $params['q']);
    }

    public function test_invoice_search_respects_staff_visibility_and_matches_literals(): void
    {
        $mine = Invoice::where('status', 'pending')->sole();
        $other = Invoice::where('status', 'paid')->sole();
        $mine->update(['customer_name' => 'Test_100%']);
        $other->update(['customer_name' => 'Test_100%']);
        $this->actingAs($mine->creator);
        foreach (['Test_100%', $mine->invoice_number] as $term) {
            $this->get(route('invoices.index', ['q' => $term]))->assertOk()
                ->assertSeeText($mine->invoice_number)->assertDontSeeText($other->invoice_number);
        }
        $this->get(route('invoices.index', ['q' => 'Test%100']))
            ->assertOk()->assertDontSeeText($mine->invoice_number);
    }

    public function test_invoice_form_retry_and_receipt_shortcuts(): void
    {
        $this->actingAs(User::where('role', 'admin')->sole());
        $this->get(route('invoices.create'))->assertOk()->assertSee('name="request_key"', false);
        $data = [
            'request_key' => (string) Str::uuid(), 'currency' => 'SDG',
            'invoice_date' => today()->toDateString(), 'customer_name' => 'Pilot',
            'items' => [['item_name' => 'Ticket', 'quantity' => 1, 'unit_price' => '1000.00']],
        ];
        $this->post(route('invoices.store'), $data)->assertSessionHasNoErrors();
        $invoice = Invoice::where('customer_name', 'Pilot')->sole();
        $show = route('invoices.show', $invoice);
        $this->post(route('invoices.store'), $data)->assertRedirect($show);
        $this->assertSame(1, Invoice::where('customer_name', 'Pilot')->count());
        $this->post(route('invoices.store'), ['customer_name' => 'Changed'] + $data)->assertStatus(409);
        $this->put(route('invoices.update', $invoice), ['status' => 'paid'] + $data)
            ->assertRedirect($show.'#record-payment');
        $this->assertSame('pending', $invoice->fresh()->status);
        foreach (['en', 'ar'] as $locale) {
            $this->withSession(['locale' => $locale])->get($show)->assertOk()
                ->assertSee('href="#record-payment"', false);
        }
        $store = route('invoices.payments.store', $invoice);
        $this->post($store, $this->paymentPayload('400.00'))->assertSessionHasNoErrors();
        $this->post($store, $this->paymentPayload('600.00'))->assertSessionHasNoErrors();
        $receipt = $invoice->payments()->latest('id')->firstOrFail();
        $this->get($show)->assertOk()->assertSee('correct=1#receipt-correction');
        $this->get($show.'?correct=1')->assertOk()->assertSee($receipt->receiptNumber());
        $this->post(route('invoices.payments.reverse', [$invoice, $receipt]), ['reason' => 'Amount correction'])
            ->assertSessionHasNoErrors();
        $this->assertSame('pending', $invoice->fresh()->status);
        $this->assertEquals(600, $invoice->fresh()->outstandingAmount());
        $report = $this->get(route('reports.team', ['currency' => 'SDG']))->assertOk();
        $this->assertEquals(600, $report->viewData('totals')->sole()->outstanding_amount);
        $this->get(route('invoices.payments.show', [$invoice, $receipt]))
            ->assertOk()->assertSeeText(__('messages.reversed_receipt_notice'));
    }

    public function test_paid_invoices_keep_separate_currency_totals_and_unique_numbers(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $this->actingAs($admin);
        $payload = [
            'branch' => 'SD',
            'customer_name' => 'Test customer',
            'invoice_date' => '2026-11-20',
            'currency' => 'SDG',
            'status' => 'paid',
            'items' => [['item_name' => 'Ticket', 'quantity' => 2, 'unit_price' => '125.25']],
        ];
        $count = Invoice::count();
        $invalid = $payload;
        unset($invalid['items'][0]['unit_price']);
        $this->post(route('invoices.store'), $invalid)->assertSessionHasErrors('items.0.unit_price');
        $this->assertSame($count, Invoice::count());

        $this->post(route('invoices.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $this->post(route('invoices.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
        $invoice = Invoice::where('customer_name', 'Test customer')->firstOrFail();
        foreach (['AE', 'GB', 'SD'] as $code) {
            $payload['branch'] = $code;
            $this->put(route('invoices.update', $invoice), $payload)->assertSessionHasNoErrors();
            $this->assertSame($code, $invoice->fresh()->branch);
            foreach (['show', 'print'] as $page) {
                $this->get(route('invoices.'.$page, $invoice))->assertOk()->assertSee(__('messages.branch_'.$code));
            }
        }
        $this->post(route('invoices.store'), array_merge($payload, ['branch' => 'XX']))->assertSessionHasErrors('branch');
        unset($payload['branch']);
        $this->put(route('invoices.update', $invoice), $payload)->assertSessionHasNoErrors();
        $this->assertSame('SD', $invoice->fresh()->branch);
        $this->put(route('invoices.update', $invoice), ['branch' => ''] + $payload)->assertSessionHasNoErrors();
        $this->assertNull($invoice->fresh()->branch);
        $this->post(route('invoices.store'), ['customer_name' => 'Default issuer', 'status' => 'draft'] + $payload)
            ->assertSessionHasNoErrors();
        $default = Invoice::where('customer_name', 'Default issuer')->firstOrFail();
        $this->assertNull($default->branch);
        foreach (['show', 'print'] as $page) {
            $this->get(route('invoices.'.$page, $default))->assertOk()
                ->assertDontSee(__('messages.branch_SD'));
        }
        $numbers = Invoice::where('customer_name', 'Test customer')->pluck('invoice_number');
        $this->assertCount(2, $numbers->unique());
        $this->get(route('dashboard'))->assertOk()->assertSee('USD 345.00')->assertSee('SDG 501.00');
    }
}
