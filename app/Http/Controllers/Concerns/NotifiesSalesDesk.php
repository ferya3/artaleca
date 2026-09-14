<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Jobs\SendSmsAlert;
use App\Mail\EnquiryReceived;
use App\Models\ContactMessage;
use App\Support\Contact;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

trait NotifiesSalesDesk
{
    /**
     * A failed mail server must never cost the company an enquiry: the record
     * is already persisted, so a delivery failure is logged and swallowed
     * rather than shown to the visitor as an error.
     */
    protected function notifySalesDesk(ContactMessage $message): void
    {
        /*
         * Queued, and dispatched before the mail: it is the one that reaches a
         * phone, and it should not wait behind an SMTP handshake. If no worker
         * is running it simply sits in the table until one is — the enquiry is
         * saved either way.
         *
         * Guarded all the same, because `dispatch` is only asynchronous when a
         * queue driver is. On `sync` — the test environment, and any server
         * whose worker was never set up — the job runs inside this request, and
         * the exception it throws to earn a retry would reach the visitor as a
         * 500 on a form whose data is already saved.
         */
        try {
            SendSmsAlert::dispatch($message->id);
        } catch (\Throwable $e) {
            Log::error('Enquiry SMS alert failed', [
                'enquiry_id' => $message->id,
                'exception' => $e->getMessage(),
            ]);
        }

        try {
            Mail::to(Contact::value('sales_email'))->send(new EnquiryReceived($message));
        } catch (\Throwable $e) {
            Log::error('Enquiry notification failed', [
                'enquiry_id' => $message->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
