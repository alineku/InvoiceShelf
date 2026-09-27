<?php

use App\Domains\Accounts\Models\User;
use App\Domains\Catalog\Http\Controllers\ItemsController;
use App\Domains\Catalog\Http\Requests\ItemsRequest;
use App\Domains\Catalog\Models\Item;
use App\Domains\Taxation\Models\Tax;
use App\Domains\Taxation\Models\TaxType;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('get items', function () {
    $response = getJson('api/v1/items?page=1');

    $response->assertOk();
});

test('item metadata only exposes sales tax types', function () {
    $companyId = User::find(1)->companies()->first()->id;
    $salesTaxType = TaxType::factory()->create([
        'company_id' => $companyId,
        'transaction_type' => TaxType::TRANSACTION_TYPE_SALES,
    ]);
    $purchaseTaxType = TaxType::factory()->create([
        'company_id' => $companyId,
        'transaction_type' => TaxType::TRANSACTION_TYPE_PURCHASES,
    ]);

    $taxTypeIds = collect(
        getJson('api/v1/items?page=1')
            ->assertOk()
            ->json('meta.tax_types')
    )->pluck('id');

    expect($taxTypeIds)
        ->toContain($salesTaxType->id)
        ->not->toContain($purchaseTaxType->id);
});

test('create item', function () {
    $item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw(),
            Tax::factory()->raw(),
        ],
    ]);

    $response = postJson('api/v1/items', $item);

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $response->getData()->data->id,
    ]);

    $response->assertOk();
});

test('store validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ItemsController::class,
        'store',
        ItemsRequest::class
    );
});

test('get item', function () {
    $item = Item::factory()->create();

    $response = getJson("api/v1/items/{$item->id}");

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);
});

test('update item', function () {
    $item = Item::factory()->create();

    $update_item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw(),
        ],
    ]);

    $response = putJson('api/v1/items/'.$item->id, $update_item);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $update_item['name'],
        'description' => $update_item['description'],
        'price' => $update_item['price'],
        'company_id' => $update_item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $item->id,
    ]);
});

test('update validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ItemsController::class,
        'update',
        ItemsRequest::class
    );
});

test('delete multiple items', function () {
    $items = Item::factory()->count(5)->create();

    $data = [
        'ids' => $items->pluck('id'),
    ];

    postJson('/api/v1/items/delete', $data)->assertOk();

    foreach ($items as $item) {
        $this->assertModelMissing($item);
    }
});

test('search items', function () {
    $filters = [
        'page' => 1,
        'limit' => 15,
        'search' => 'doe',
        'price' => 6,
        'unit' => 'kg',
    ];

    $queryString = http_build_query($filters, '', '&');

    $response = getJson('api/v1/items?'.$queryString);

    $response->assertOk();
});

test('create item with fixed amount tax', function () {
    $item = Item::factory()->raw([
        'taxes' => [
            Tax::factory()->raw([
                'calculation_type' => 'fixed',
                'fixed_amount' => 5000,
            ]),
        ],
    ]);

    $response = postJson('api/v1/items', $item);

    $response->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'description' => $item['description'],
        'price' => $item['price'],
        'company_id' => $item['company_id'],
    ]);

    $this->assertDatabaseHas('taxes', [
        'item_id' => $response->getData()->data->id,
        'calculation_type' => 'fixed',
        'fixed_amount' => 5000,
    ]);
});

test('create item with catalogue details', function () {
    $item = Item::factory()->raw([
        'sku' => '10014',
        'brand' => 'Brand 1',
        'packaging' => 'Bucket',
        'weight' => 500,
        'weight_unit' => 'g',
        'pieces_per_carton' => 10,
    ]);

    $response = postJson('api/v1/items', $item)->assertOk();

    $this->assertDatabaseHas('items', [
        'name' => $item['name'],
        'sku' => '10014',
        'brand' => 'Brand 1',
        'packaging' => 'Bucket',
        'weight_unit' => 'g',
        'pieces_per_carton' => 10,
    ]);

    expect($response->json('data.weight_in_kg'))->toEqual(0.5)
        ->and($response->json('data.pieces_per_carton'))->toBe(10);
});

test('catalogue details are validated', function () {
    $item = Item::factory()->raw([
        'weight' => -1,
        'weight_unit' => 'lb',
        'pieces_per_carton' => 0,
    ]);

    postJson('api/v1/items', $item)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['weight', 'weight_unit', 'pieces_per_carton']);
});

test('search items by code or brand', function () {
    Item::factory()->create(['name' => 'Lemon', 'sku' => 'LEM-45', 'brand' => 'Brand 4']);
    Item::factory()->create(['name' => 'Grape', 'sku' => 'GRP-43', 'brand' => 'Brand 9']);

    expect(getJson('api/v1/items?search=LEM-45')->assertOk()->json('data.*.name'))
        ->toBe(['Lemon']);

    expect(getJson('api/v1/items?search=Brand 9')->assertOk()->json('data.*.name'))
        ->toBe(['Grape']);
});
