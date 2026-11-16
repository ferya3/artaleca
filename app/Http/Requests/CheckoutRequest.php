<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Shop\Shop;
use App\Support\Mobile;
use Illuminate\Validation\Rule;

/**
 * The checkout form.
 *
 * Extends the contact form for its spam defences — the honeypot and the
 * signed render time — and nothing else; the fields are its own.
 *
 * Persian and Arabic digits are turned into Latin ones before any rule runs.
 * A buyer typing their postal code on a Persian keyboard types ۱۲۳…, and a
 * form that rejected that as "not a number" would be rejecting the customer.
 */
class CheckoutRequest extends ContactRequest
{
    protected function prepareForValidation(): void
    {
        $digits = fn ($v) => is_string($v) ? strtr($v, [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]) : $v;

        $merge = [];

        foreach (['mobile', 'national_id', 'economic_code', 'postal_code'] as $field) {
            if ($this->has($field)) {
                $merge[$field] = preg_replace('/[\s\-]+/', '', (string) $digits($this->input($field)));
            }
        }

        if (isset($merge['mobile'])) {
            $merge['mobile'] = Mobile::normalize($merge['mobile']) ?? $merge['mobile'];
        }

        $this->merge($merge);
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'min:2', 'max:120'],
            'mobile' => ['required', 'string', 'regex:/^09\d{9}$/'],
            'email' => ['nullable', 'string', 'email:filter', 'max:180'],
            'company' => ['nullable', 'string', 'max:160'],
            'national_id' => ['nullable', 'regex:/^\d{10,11}$/'],
            'economic_code' => ['nullable', 'regex:/^\d{10,14}$/'],

            'province' => ['required', 'string', Rule::in(array_keys(Shop::provinces()))],
            'city' => ['required', 'string', 'min:2', 'max:80'],
            'address' => ['required', 'string', 'min:10', 'max:500'],
            'postal_code' => ['nullable', 'regex:/^\d{10}$/'],
            'notes' => ['nullable', 'string', 'max:1000'],

            'payment_method' => ['required', Rule::in(Shop::paymentMethods())],
            'shipping_method' => ['required', Rule::in(Shop::shippingMethods())],
            'consent' => ['accepted'],

            'website' => ['prohibited'],
            'started_at' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return collect([
            'customer_name', 'mobile', 'email', 'company', 'national_id', 'economic_code',
            'province', 'city', 'address', 'postal_code', 'notes', 'payment_method', 'shipping_method',
        ])->mapWithKeys(fn ($f) => [$f => __('shop.fields.'.$f)])->all();
    }

    public function messages(): array
    {
        return array_merge(parent::messages(), [
            'mobile.regex' => __('shop.errors.mobile'),
            'consent.accepted' => __('shop.errors.consent'),
        ]);
    }
}
