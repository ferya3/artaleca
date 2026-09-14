<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\ContactMessage;
use App\Support\Sms;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * A new enquiry, on the sales manager's phone.
 *
 * Queued rather than sent inline: the visitor pressed "send" on a form, and
 * what happens next should be a confirmation page, not a wait on an SMS panel.
 * It also means a panel that is briefly down gets retried instead of losing the
 * alert — the enquiry itself is saved either way.
 *
 * Only the id is carried, not the model: by the time this runs the record may
 * already have been read in the panel, and the message should describe the
 * enquiry as it is.
 */
class SendSmsAlert implements ShouldQueue
{
    use Queueable;

    /** Three attempts over a couple of minutes; after that it is not a blip. */
    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [10, 60];

    public function __construct(public int $enquiryId) {}

    public function handle(): void
    {
        if (! Sms::enabled()) {
            return;
        }

        $mobile = Sms::salesMobile();

        if ($mobile === '') {
            return;
        }

        /*
         * A panel that is not configured is not a temporary failure, so it does
         * not go round the retry loop three times: it is logged once and
         * dropped. A panel that *is* configured and refuses the message throws,
         * which is what earns a retry.
         */
        if ($reason = Sms::configurationError()) {
            Log::warning('SMS alert skipped: the panel is not configured', ['reason' => $reason]);

            return;
        }

        $enquiry = ContactMessage::find($this->enquiryId);

        if (! $enquiry) {
            return;
        }

        [$status, $detail] = Sms::send($mobile, self::text($enquiry));

        if ($status !== Sms::STATUS_SENT) {
            /*
             * The detail, never the message body: an enquiry carries a person's
             * name, number and project, and none of that belongs in a log file.
             */
            Log::warning('SMS alert not delivered', [
                'enquiry_id' => $enquiry->id,
                'status' => $status,
                'detail' => $detail,
            ]);

            // Throwing is what puts it back on the queue for another attempt.
            if ($status === Sms::STATUS_FAILED) {
                throw new \RuntimeException('The SMS panel refused the alert: '.$detail);
            }
        }
    }

    /**
     * What arrives on the phone, and why it is this short.
     *
     * A Persian SMS is UCS-2, which is 70 characters per part, and every part
     * is charged. So this is not a copy of the enquiry — it is the part someone
     * acts on immediately: what kind of enquiry, who, and the number to call
     * back. The rest is in the panel and in the email, both of which carry the
     * whole thing.
     *
     * No link, deliberately. A URL costs 40 characters of a 70-character part,
     * and Iranian panels filter links on shared sender lines.
     */
    public static function text(ContactMessage $enquiry): string
    {
        $short = fn (?string $value, int $limit): string => trim(mb_substr((string) $value, 0, $limit));

        $lines = [__('admin.sms.alert.'.($enquiry->type === 'quote' ? 'quote' : 'message'))];

        $who = $short($enquiry->name, 40);

        if (filled($enquiry->company)) {
            $who .= ' — '.$short($enquiry->company, 40);
        }

        $lines[] = $who;

        if (filled($enquiry->phone)) {
            $lines[] = $short($enquiry->phone, 20);
        }

        $what = array_filter([
            $short($enquiry->product?->name, 40),
            $short($enquiry->quantity, 24),
        ], 'filled');

        if ($what !== []) {
            $lines[] = implode(' / ', $what);
        }

        return implode("\n", $lines);
    }
}
