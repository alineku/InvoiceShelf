<?php

use App\Domains\Accounts\Models\CompanySetting;
use App\Domains\Accounts\Models\User;
use App\Domains\Sales\Models\Invoice;
use App\Support\Formatting\CompanyDate;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $this->companyId = User::find(1)->companies()->first()->id;
});

test('a company on the gregorian calendar keeps gregorian dates', function () {
    expect(CompanyDate::format('2026-09-27', 'Y/m/d', $this->companyId))->toBe('2026/09/27');
});

test('a company on the jalali calendar sees persian dates', function () {
    CompanySetting::setSettings(['calendar' => CompanyDate::JALALI], $this->companyId);

    expect(CompanyDate::format(Carbon::parse('2026-09-27'), 'Y/m/d', $this->companyId))->toBe('1405/07/05')
        ->and(CompanyDate::format('2026-03-21', 'Y-m-d', $this->companyId))->toBe('1405-01-01');
});

test('an unknown calendar value falls back to gregorian', function () {
    CompanySetting::setSettings(['calendar' => 'lunar'], $this->companyId);

    expect(CompanyDate::calendar($this->companyId))->toBe(CompanyDate::GREGORIAN);
});

test('empty dates render as an empty string', function () {
    expect(CompanyDate::format(null, 'Y/m/d', $this->companyId))->toBe('');
});

test('invoice dates follow the company calendar', function () {
    CompanySetting::setSettings([
        'calendar' => CompanyDate::JALALI,
        'carbon_date_format' => 'Y/m/d',
        'invoice_use_time' => 'NO',
    ], $this->companyId);

    $invoice = Invoice::factory()->create([
        'company_id' => $this->companyId,
        'invoice_date' => '2026-09-04',
        'due_date' => '2026-09-27',
    ]);

    expect($invoice->formattedInvoiceDate)->toBe('1405/06/13')
        ->and($invoice->formattedDueDate)->toBe('1405/07/05');
});
