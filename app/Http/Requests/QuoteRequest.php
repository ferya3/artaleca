<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Support\DeliveryTerms;
use Illuminate\Validation\Rule;

/**
 * The RFQ form: everything the contact form asks plus the commercial detail
 * the sales desk needs to price a shipment.
 *
 * The phone number is required here and optional on the contact form, and the
 * difference is not an oversight. A price for expanded clay depends on the
 * grade, the volume and where it is going, so a quote is settled in a call —
 * and the enquiry alert that reaches the sales manager's phone carries this
 * number as the thing to act on. An RFQ with no number is a lead nobody can
 * answer.
 */
class QuoteRequest extends ContactRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')->where('is_active', true)],
            'quantity' => ['required', 'string', 'max:60'],

            // The same shape the representation form accepts: digits, spaces
            // and the punctuation people put in a number, six characters up.
            'phone' => ['required', 'string', 'max:40', 'regex:/^[\d\s+()\-\.]{6,40}$/'],
            // Read from the same place the select is built from, so a term an
            // editor removes stops validating and one they add starts working.
            'delivery_terms' => ['nullable', Rule::in(DeliveryTerms::codes())],
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
