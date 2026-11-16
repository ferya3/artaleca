<x-layouts.app>
    <x-page-header :title="__('shop.track_title')" :lead="__('shop.track_intro')" />

    <section class="py-section">
        <div class="container-page max-w-xl">
            <form method="POST" action="{{ route('orders.lookup') }}" class="space-y-5">
                @csrf
                <x-form.field name="number" :label="__('shop.order_number')" :placeholder="__('shop.track_number_placeholder')" required />
                <x-form.field name="mobile" type="tel" :label="__('shop.fields.mobile')" placeholder="09123456789" required />
                <x-button type="submit" variant="accent">{{ __('shop.track_submit') }}</x-button>
            </form>
        </div>
    </section>
</x-layouts.app>
