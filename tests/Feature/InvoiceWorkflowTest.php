<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\TestCase;

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
        ]);
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->seed(DatabaseSeeder::class);
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
        $numbers = Invoice::where('customer_name', 'Test customer')->pluck('invoice_number');
        $this->assertCount(2, $numbers->unique());
        $this->get(route('dashboard'))->assertOk()->assertSee('USD 345.00')->assertSee('SDG 501.00');
    }
}
