<?php

namespace App\Domains\Catalog\Http\Requests;

use App\Domains\Catalog\Models\Item;
use App\Domains\Metadata\Http\Requests\Concerns\ValidatesCustomFields;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Incoming payload for creating or editing a catalog item.
 *
 * Only what describes the item is taken from the client. The owning company,
 * the creator and the currency are stamped from the request context further
 * down, so they are absent from the rules and never survive validation even
 * when a client sends them. Item taxes ride along outside these rules and are
 * read straight off the request by the controller.
 */
class ItemsRequest extends FormRequest
{
    use ValidatesCustomFields;

    /**
     * Access is settled by the item policy in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * A name and a price are demanded, the unit and the description may be
     * left out or sent empty; those four are presence checks only, so the
     * price is whatever the column makes of the submitted value. The
     * catalogue details (code, brand, packaging, weight, pieces per carton)
     * are optional but type-checked.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required'],
            'price' => ['required'],
            'unit_id' => ['nullable'],
            'description' => ['nullable'],
            'sku' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'packaging' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'weight_unit' => ['nullable', Rule::in(array_keys(Item::WEIGHT_UNITS))],
            'pieces_per_carton' => ['nullable', 'integer', 'min:1'],
            ...$this->customFieldRules(),
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $this->validateCustomFieldAnswers($validator);
    }

    /**
     * The columns written to the catalogue item.
     *
     * The answers arrive alongside them and are persisted separately, so they
     * are taken out here rather than left for the model to ignore.
     *
     * @return array<string, mixed>
     */
    public function getItemPayload(): array
    {
        return $this->withoutCustomFields($this->validated());
    }
}
