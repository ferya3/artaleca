<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

/**
 * The RFQ form: everything the contact form asks plus the commercial detail
 * the sales desk needs to price a shipment.
 */
class QuoteRequest extends ContactRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'quantity' => ['required', 'string', 'max:60'],
            'delivery_terms' => ['nullable', Rule::in(['EXW', 'FOB', 'CFR', 'CIF', 'DAP'])],
            // The commercial context belongs in `message`, so it stays optional
            // here — a buyer should be able to send a quantity and nothing else.
            'message' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'product_id' => __('form.product'),
            'quantity' => __('form.quantity'),
            'delivery_terms' => __('form.delivery_terms'),
        ]);
    }
}
