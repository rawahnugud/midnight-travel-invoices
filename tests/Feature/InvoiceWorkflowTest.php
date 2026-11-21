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
