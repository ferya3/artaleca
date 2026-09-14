<x-layouts.app>

    @php
        // Grouped by category so a long grade list stays scannable in the select.
        $productOptions = $products
            ->groupBy(fn ($product) => (string) $product->category?->name)
            ->map(fn ($group) => $group->mapWithKeys(fn ($product) => [$product->id => $product->name]));
    @endphp

    <x-page-header
        :eyebrow="content('nav.quote')"
        :title="__('form.quote_title')"
        :lead="__('form.quote_intro')"
    />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12 lg:gap-16">

            <div class="min-w-0 lg:col-span-7" id="form">
                <x-form.status />

                <form method="POST" action="{{ route('quote.store') }}" class="relative grid gap-5 sm:grid-cols-2">
                    @csrf
                    <x-form.honeypot />

                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        {{-- Required, like the quantity beside it: a grade and a
                             volume are what a price is calculated from, and an
                             RFQ missing either one is a round of emails before
                             anyone can answer it. The markers are written out
                             rather than taken from `x-form.field`, because that
                             component has no option groups and the grades are
                             worth grouping by category. --}}
                        <label for="f-product_id" class="text-sm font-medium text-ink-800">
                            {{ __('form.product') }}
                            <span class="text-brand-600" aria-hidden="true">*</span>
                            <span class="sr-only">({{ content('common.required') }})</span>
                        </label>
                        <select id="f-product_id" name="product_id" required
                                class="w-full border border-ink-300 bg-surface px-3.5 py-3 text-sm text-ink-900 focus:border-ink-500 focus:outline-none focus:ring-4 focus:ring-brand-500/12">
                            <option value="">{{ __('form.select_placeholder') }}</option>
                            @foreach ($productOptions as $categoryName => $options)
                                <optgroup label="{{ $categoryName }}">
                                    @foreach ($options as $id => $label)
                                        <option value="{{ $id }}" @selected((int) old('product_id', $selected?->id) === $id)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        @error('product_id')
                            <p class="text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-form.field
                        name="quantity"
                        :label="__('form.quantity')"
                        :placeholder="__('form.quantity_placeholder')"
                        required
                    />

                    {{-- The options were five bare Incoterm codes, which meant
                         nothing to most of the people filling this in. Each one
                         now carries a sentence, and the whole list is editable
                         in Panel → Settings → Delivery terms — see
                         `App\Support\DeliveryTerms`. --}}
                    <x-form.field
                        name="delivery_terms"
                        type="select"
                        :label="__('form.delivery_terms')"
                        :hint="__('form.delivery_terms_hint')"
                        :options="\App\Support\DeliveryTerms::options()"
                    />

                    <div class="sm:col-span-2 border-t border-hairline pt-5"></div>

                    <x-form.field name="name" :label="__('form.name')" required />
                    <x-form.field name="company" :label="__('form.company')" />
                    <x-form.field name="email" type="email" :label="__('form.email')" required />
                    {{-- Required on this form only, with the reason next to it:
                         a quote is settled in a call, and the alert that
                         reaches the sales manager carries this number. --}}
                    <x-form.field
                        name="phone"
                        type="tel"
                        :label="__('form.phone')"
                        :hint="__('form.phone_quote_hint')"
                        required
                    />

                    <x-form.field
                        name="country_code"
                        :label="__('form.country')"
                        placeholder="IR"
                        class="sm:col-span-2"
                    />

                    <x-form.field name="message" type="textarea" :label="__('form.message')" :rows="5" class="sm:col-span-2" />

                    <div class="sm:col-span-2">
                        <x-form.consent />
                    </div>

                    <div class="sm:col-span-2">
                        <x-button type="submit" size="lg">{{ content('common.submit') }}</x-button>
                    </div>
                </form>
            </div>

            <aside class="min-w-0 lg:col-span-5">
                <div class="panel-muted p-6 lg:sticky lg:top-28">
                    {{-- A step down from the page's own headings: this is the
                         column beside the form, not a section of its own, and
                         at 18px it competed with the thing it sits next to. --}}
                    <h2 class="text-base font-bold text-ink-950">{{ content('home.export_title') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ content('home.export_body') }}</p>

                    <x-stat-strip tone="light" class="mt-6 border border-hairline" />

                    <div class="mt-6 space-y-2 border-t border-hairline pt-5 text-sm">
                        <p class="text-ink-500">{{ content('contact.export_desk') }}</p>
                        <a class="ltr-run block font-medium text-ink-900 hover:text-brand-600"
                           href="mailto:{{ \App\Support\Contact::value('export_email') }}">{{ \App\Support\Contact::value('export_email') }}</a>
                        <a class="ltr-run block text-ink-600 hover:text-brand-600"
                           href="tel:{{ \App\Support\Contact::tel('sales_phone') }}">{{ \App\Support\Contact::value('sales_phone') }}</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>

</x-layouts.app>
