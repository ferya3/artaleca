<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactMessage;
use App\Support\Bale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A new enquiry, on the sales desk's phone.
 *
 * Queued rather than sent inline: the visitor pressed "send" on a form, and
 * what happens next should be a confirmation page, not a wait on a third-party
 * API in another country. It also means a messenger that is briefly down gets
 * retried instead of losing the alert — the enquiry itself is already saved
 * either way.
 *
 * Only the id is carried, not the model: by the time this runs the record may
 * have been read or answered in the panel, and the message should describe the
 * enquiry as it is, not as it was when the queue was busy.
 */
class SendBaleAlert implements ShouldQueue
{
    use Queueable;

    /** Three attempts over a couple of minutes; after that it is not a blip. */
    public int $tries = 3;

    public array $backoff = [10, 60];

    public function __construct(public int $enquiryId) {}

    public function handle(): void
    {
        if (! Bale::enabled()) {
            return;
        }

        $enquiry = ContactMessage::find($this->enquiryId);

        if (! $enquiry) {
            return;
        }

        Bale::send($this->text($enquiry));
    }

    /**
     * What arrives on the phone.
     *
     * Ordered by what the person reads first and acts on fastest: who it is,
     * then the number to call them back on, then what they want. The link at
     * the end opens the enquiry in the panel — the only thing in the message
     * that is not already the whole story.
     */
    private function text(ContactMessage $enquiry): string
    {
        $e = fn (?string $value): string => e((string) $value);

        $lines = [
            ($enquiry->type === 'quote' ? '📋 <b>' : '📩 <b>')
                .$e(__('admin.enquiry.type_'.$enquiry->type)).'</b>',
            '',
            '<b>'.$e($enquiry->name).'</b>',
        ];

        foreach ([
            $enquiry->company,
            $enquiry->phone,
            $enquiry->email,
        ] as $detail) {
            if (filled($detail)) {
                $lines[] = $e($detail);
            }
        }

        $facts = array_filter([
            __('form.product') => $enquiry->product?->name,
            __('form.quantity') => $enquiry->quantity,
            __('form.delivery_terms') => $enquiry->delivery_terms,
            __('form.subject') => $enquiry->subject,
        ], 'filled');

        if ($facts !== []) {
            $lines[] = '';

            foreach ($facts as $label => $value) {
                $lines[] = $e($label).': '.$e((string) $value);
            }
        }

        if (filled($enquiry->message)) {
            $lines[] = '';
            // Long messages are trimmed: this is an alert, and the whole thing
            // is one tap away in the panel.
            $lines[] = '<i>'.$e(str($enquiry->message)->limit(500)->toString()).'</i>';
        }

        $lines[] = '';
        $lines[] = route('admin.enquiries.show', $enquiry);

        return implode("\n", $lines);
    }
}
