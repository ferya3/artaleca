<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['required', 'string', 'email:filter', 'max:180'],
            'phone' => ['nullable', 'string', 'max:40', 'regex:/^[\d\s+()\-\.]{6,40}$/'],
            'country_code' => ['nullable', 'string', 'size:2', 'alpha'],
            'subject' => ['nullable', 'string', 'max:180'],
            'message' => ['required', 'string', 'min:10', 'max:4000'],
            'consent' => ['accepted'],

            // Honeypot: a real browser leaves this hidden field empty.
            'website' => ['prohibited'],
            // Round-trip time, stamped into the form when it is rendered.
            'started_at' => ['required', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('form.name'),
            'company' => __('form.company'),
            'email' => __('form.email'),
            'phone' => __('form.phone'),
            'subject' => __('form.subject'),
            'message' => __('form.message'),
            'consent' => __('form.consent'),
        ];
    }

    public function messages(): array
    {
        return [
            'website.prohibited' => __('form.spam_detected'),
            'consent.accepted' => __('form.consent_required'),
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                /*
                 * Timing check. The signed timestamp is minted when the form is
                 * rendered; a submission that arrives within three seconds was
                 * not typed by a human, and one older than two hours is a
                 * replayed or scraped form.
                 */
                $started = $this->signedTimestamp();

                if ($started === null) {
                    $validator->errors()->add('started_at', __('form.expired'));

                    return;
                }

                $elapsed = now()->getTimestamp() - $started;

                if ($elapsed < 3 || $elapsed > 7200) {
                    $validator->errors()->add('started_at', __('form.expired'));
                }
            },
        ];
    }

    /**
     * Verify and unpack the `timestamp.signature` token so the elapsed-time
     * check cannot simply be forged by editing the hidden input.
     */
    private function signedTimestamp(): ?int
    {
        $parts = explode('.', (string) $this->input('started_at'), 2);

        if (count($parts) !== 2 || ! ctype_digit($parts[0])) {
            return null;
        }

        $expected = hash_hmac('sha256', $parts[0], (string) config('app.key'));

        return hash_equals($expected, $parts[1]) ? (int) $parts[0] : null;
    }
}
