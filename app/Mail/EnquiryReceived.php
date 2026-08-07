<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EnquiryReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $enquiry) {}

    public function envelope(): Envelope
    {
        $label = $this->enquiry->type === 'quote' ? 'RFQ' : 'Enquiry';

        return new Envelope(
            subject: sprintf(
                '[%s] %s — %s',
                $label,
                $this->enquiry->company ?: $this->enquiry->name,
                $this->enquiry->subject ?: 'New website enquiry',
            ),
            // Reply-To, never From: sending as the visitor would fail SPF/DKIM
            // and land the notification in spam.
            replyTo: [new Address($this->enquiry->email, $this->enquiry->name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.enquiry-received',
            with: ['enquiry' => $this->enquiry->loadMissing('product')],
        );
    }
}
