<?php

use App\Domains\Accounts\Models\User;
use App\Domains\Sales\Models\Invoice;
use App\Platform\Pdf\Rendering\PdfTemplateUtils;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;

/**
 * The Persian, right-to-left sales invoice laid out like the business's paper
 * invoice. It is written for the Gotenberg driver, but renders as HTML first.
 */
beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->company = $user->companies()->first();

    Sanctum::actingAs($user, ['*']);
});

test('the persian invoice is offered in the template picker', function () {
    $names = array_column(PdfTemplateUtils::getFormattedTemplates('invoice', ''), 'name');

    expect($names)->toContain('invoice-miras');
});

test('the persian invoice renders right to left with the amount in words', function () {
    $invoice = Invoice::factory()->hasItems(1)->create([
        'company_id' => $this->company->id,
        'template_name' => 'invoice-miras',
    ]);

    $html = get("/invoices/pdf/{$invoice->unique_hash}?preview")->assertOk()->getContent();

    expect($html)->toContain('dir="rtl"')
        ->and($html)->toContain('صورتحساب فروش کالا و خدمات')
        ->and($html)->toContain('مبلغ به حروف')
        ->and($html)->toContain($invoice->items->first()->name);
});
