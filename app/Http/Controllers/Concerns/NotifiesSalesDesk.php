<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Mail\EnquiryReceived;
use App\Models\ContactMessage;
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
        try {
            Mail::to(config('site.contact.sales_email'))->send(new EnquiryReceived($message));
        } catch (\Throwable $e) {
            Log::error('Enquiry notification failed', [
                'enquiry_id' => $message->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
