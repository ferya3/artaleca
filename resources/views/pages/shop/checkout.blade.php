<x-layouts.app>
    @php
        $radio = 'flex cursor-pointer gap-3 rounded-lg border border-ink-300 bg-surface p-4 transition-colors has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/60';
        $defaultPayment = old('payment_method', $paymentMethods[0] ?? null);
        $defaultShipping = old('shipping_method', \App\Shop\Shop::SHIPPING_FREIGHT);
    @endphp

    <x-page-header :title="__('shop.checkout')" />

    <section class="py-section">
        <div class="container-page grid gap-12 lg:grid-cols-12">
            <div class="min-w-0 lg:col-span-7">
                <x-form.status />

                <form method="POST" action="{{ route('checkout.store') }}" class="relative space-y-10" id="checkout">
                    @csrf
                    <x-form.honeypot />

                    <fieldset class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 text-lg font-bold text-ink-950">{{ __('shop.sections.contact') }}</legend>
                        <x-form.field name="customer_name" :label="__('shop.fields.customer_name')" required class="sm:col-span-2" />
                        <x-form.field name="mobile" type="tel" :label="__('shop.fields.mobile')" :hint="__('shop.hints.mobile')" placeholder="09123456789" required />
                        <x-form.field name="email" type="email" :label="__('shop.fields.email')" />
                    </fieldset>

                    <fieldset class="grid gap-5 sm:grid-cols-2">
                        <legend class="mb-4 text-lg font-bold text-ink-950">{{ __('shop.sections.delivery') }}</legend>
                        <x-form.field name="province" type="select" :label="__('shop.fields.province')" :options="\App\Shop\Shop::provinces()" required />
                        <x-form.field name="city" :label="__('shop.fields.city')" required />
                        <x-form.field name="address" type="textarea" :rows="3" :label="__('shop.fields.address')" :hint="__('shop.hints.address')" required class="sm:col-span-2" />
                        <x-form.field name="postal_code" :label="__('shop.fields.postal_code')" />
                        <x-form.field name="notes" type="textarea" :rows="2" :label="__('shop.fields.notes')" :hint="__('shop.hints.notes')" class="sm:col-span-2" />
                    </fieldset>

                    <fieldset class="space-y-3">
                        <legend class="mb-4 text-lg font-bold text-ink-950">{{ __('shop.sections.shipping') }}</legend>
                        @foreach (\App\Shop\Shop::shippingMethods() as $method)
                            <label class="{{ $radio }}">
                                <input type="radio" name="shipping_method" value="{{ $method }}" @checked($defaultShipping === $method) required class="mt-1 accent-brand-600">
                                <span>
                                    <span class="block font-semibold text-ink-950">{{ __('shop.shipping.'.$method) }}</span>
                                    <span class="mt-1 block text-sm text-ink-600">{{ __('shop.shipping.'.$method.'_hint') }}</span>
                                </span>
                            </label>
                        @endforeach
                        @error('shipping_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </fieldset>

                    <fieldset class="space-y-3">
                        <legend class="mb-4 text-lg font-bold text-ink-950">{{ __('shop.sections.payment') }}</legend>
                        @forelse ($paymentMethods as $method)
                            <label class="{{ $radio }}">
                                <input type="radio" name="payment_method" value="{{ $method }}" @checked($defaultPayment === $method) required class="mt-1 accent-brand-600">
                                <span>
                                    <span class="block font-semibold text-ink-950">{{ __('shop.payment.'.$method) }}</span>
                                    <span class="mt-1 block text-sm text-ink-600">{{ __('shop.payment.'.$method.'_hint') }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('shop.payment.none') }}</p>
                        @endforelse
                        @error('payment_method') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @if (app()->getLocale() !== 'fa')
                            <p class="text-xs text-ink-500">{{ __('shop.foreign_note') }}</p>
                        @endif
                    </fieldset>

                    <details class="rounded-lg border border-hairline bg-surface p-4" @if ($errors->hasAny(['company', 'national_id', 'economic_code']) || old('company')) open @endif>
                        <summary class="cursor-pointer font-semibold text-ink-950">{{ __('shop.sections.invoice') }}</summary>
                        <p class="mt-2 text-sm text-ink-600">{{ __('shop.sections.invoice_hint') }}</p>
                        <div class="mt-4 grid gap-5 sm:grid-cols-2">
                            <x-form.field name="company" :label="__('shop.fields.company')" class="sm:col-span-2" />
                            <x-form.field name="national_id" :label="__('shop.fields.national_id')" />
                            <x-form.field name="economic_code" :label="__('shop.fields.economic_code')" />
                        </div>
                    </details>

                    <div>
                        <label class="flex items-start gap-3 text-sm text-ink-700">
                            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required class="mt-1 accent-brand-600">
                            <span>{!! __('shop.consent', ['terms' => e(route('legal.terms'))]) !!}</span>
                        </label>
                        @error('consent') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <x-button type="submit" variant="accent" size="lg" class="w-full sm:w-auto" :disabled="$paymentMethods === []">
                        {{ __('shop.place_order') }}
                    </x-button>
                </form>
            </div>

            {{-- First on a phone: the total is the thing to have read before
                 pressing the button, not something to scroll past after it. --}}
            <aside class="order-first min-w-0 lg:order-none lg:col-span-5">
                <div class="panel-muted p-6 lg:sticky lg:top-28">
                    <h2 class="mb-4 text-base font-bold text-ink-950">{{ __('shop.sections.summary') }}</h2>
                    <ul class="mb-5 space-y-3 text-sm">
                        @foreach ($lines as $line)
                            <li class="flex justify-between gap-4">
                                <span class="text-ink-700">{{ $line['variant']->product->name }} — {{ $line['variant']->name }}
                                    <span class="block text-xs text-ink-500">{{ number_format($line['quantity']) }} {{ $line['variant']->unit }}</span></span>
                                <span class="tabular shrink-0 font-medium text-ink-900">{{ \App\Shop\Shop::money($line['line_total']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <x-shop.totals :totals="$totals" class="border-t border-hairline pt-4" />
                    <p class="mt-4 text-xs leading-relaxed text-ink-500">{{ __('shop.shipping_note') }}</p>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.app>
