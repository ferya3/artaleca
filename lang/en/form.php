<?php

return [
    'name' => 'Full name',
    'company' => 'Company',
    'email' => 'Email',
    'phone' => 'Phone',
    'phone_quote_hint' => 'Please give a number you answer: the sales desk quotes by phone, on this number.',
    'country' => 'Country',
    'subject' => 'Subject',
    'message' => 'Message',
    'product' => 'Grade of interest',
    'quantity' => 'Volume required',
    'quantity_placeholder' => 'e.g. 500 m³',
    'delivery_terms' => 'Delivery terms',
    'delivery_terms_hint' => 'Leave it blank if you are not sure — the sales desk will settle it on the call.',

    /*
     * The shipped list, shown until an editor writes their own in
     * Panel → Settings → Delivery terms. The code is what gets stored on the
     * enquiry; this sentence is only ever read by the buyer.
     */
    'delivery_terms_options' => [
        'EXW' => 'Ex works, collected by the buyer',
        'FOB' => 'Free on board, port of loading',
        'CFR' => 'Freight to destination port, no insurance',
        'CIF' => 'Freight and insurance to destination port',
        'DAP' => 'Delivered to your site',
    ],
    'consent' => 'privacy policy consent',
    'consent_label' => 'I agree that the details above may be stored and processed in order to answer this enquiry.',
    'consent_required' => 'Please accept the privacy policy before sending the form.',

    'contact_title' => 'Send a message',
    'contact_intro' => 'For technical questions, a plant visit or a commercial partnership, use the form below.',
    'contact_success' => 'Your message has been received. Our team will reply on the next working day.',

    'quote_title' => 'Request a quotation',
    'quote_intro' => 'The more precise the specification and volume, the faster and firmer the price we can return.',
    'quote_success' => 'Your request has been received. Our sales desk will be in touch within one working day.',

    'spam_detected' => 'This submission could not be verified.',
    'expired' => 'This form has expired. Please refresh the page and try again.',
    'has_errors' => 'Please correct the following:',
    'select_placeholder' => '— Select —',
    'territory' => 'Requested territory',
    'territory_placeholder' => 'e.g. Isfahan province — Kashan',
    'activity' => 'Current line of business',
    'activity_placeholder' => 'e.g. building materials retail',
    'experience_years' => 'Years trading',
    'warehouse_m2' => 'Storage area (m²)',
    'monthly_volume' => 'Estimated monthly volume',
    'monthly_volume_placeholder' => 'e.g. 300 m³',
];
