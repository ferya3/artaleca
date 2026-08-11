<?php

declare(strict_types=1);

namespace App\Http\Requests;

/**
 * An application to represent the plant in a territory.
 *
 * Stricter than the contact form on the things that decide whether an
 * application can be assessed at all: a distributor without a company name, a
 * phone number or a territory is not a lead the sales desk can act on, and
 * asking for them here is cheaper than a round of emails later.
 *
 * The rest — years trading, warehouse area, expected volume — is genuinely
 * optional. An applicant who does not know their monthly volume yet should not
 * be stopped at the form.
 */
class RepresentationRequest extends ContactRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'company' => ['required', 'string', 'min:2', 'max:160'],
            'phone' => ['required', 'string', 'max:40', 'regex:/^[\d\s+()\-\.]{6,40}$/'],

            'territory' => ['required', 'string', 'min:2', 'max:120'],
            'activity' => ['required', 'string', 'min:2', 'max:180'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:100'],
            'warehouse_m2' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'monthly_volume' => ['nullable', 'string', 'max:60'],

            // The structured fields above already say who is applying, so the
            // free text is where they explain anything unusual — not a hurdle.
            'message' => ['nullable', 'string', 'max:4000'],
        ]);
    }

    public function attributes(): array
    {
        return array_merge(parent::attributes(), [
            'territory' => __('form.territory'),
            'activity' => __('form.activity'),
            'experience_years' => __('form.experience_years'),
            'warehouse_m2' => __('form.warehouse_m2'),
            'monthly_volume' => __('form.monthly_volume'),
        ]);
    }

    /**
     * The form-specific answers, ready for the `details` column.
     *
     * Empty values are dropped rather than stored as nulls, so the admin can
     * render whatever is present without deciding what counts as absent.
     *
     * @return array<string, int|string>
     */
    public function details(): array
    {
        $details = array_filter(
            $this->safe()->only([
                'territory', 'activity', 'experience_years', 'warehouse_m2', 'monthly_volume',
            ]),
            'filled',
        );

        /*
         * The numbers are stored as numbers. Validation proves a field is an
         * integer but does not convert it, so a real form post arrives as the
         * string "12" — which would sort and compare as text the first time
         * anyone wants applicants ordered by warehouse size.
         */
        foreach (['experience_years', 'warehouse_m2'] as $numeric) {
            if (isset($details[$numeric])) {
                $details[$numeric] = (int) $details[$numeric];
            }
        }

        return $details;
    }
}
