<x-layouts.admin :title="__('admin.dashboard')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.dashboard') }}</h1>

    <dl class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-lg border border-hairline bg-hairline shadow-soft lg:grid-cols-4">
        @foreach ($stats as $key => $value)
            <div class="bg-white px-5 py-6">
                <dd class="tabular ltr-run text-3xl font-bold {{ $key === 'new_enquiries' && $value > 0 ? 'text-clay-600' : 'text-ink-950' }}">
                    {{ number_format($value) }}
                </dd>
                <dt class="mt-1.5 text-xs text-ink-500">{{ __('admin.stats.'.$key) }}</dt>
            </div>
        @endforeach
    </dl>

    <section class="mt-10">
        <div class="flex items-center justify-between">
            <h2 class="text-base font-bold text-ink-950">{{ __('admin.enquiries') }}</h2>
            <a href="{{ route('admin.enquiries.index') }}" class="text-sm font-medium text-clay-600 hover:underline">
                {{ __('common.view_all') }}
            </a>
        </div>

        @if ($recent->isEmpty())
            <p class="mt-4 rounded-lg border border-dashed border-ink-300 bg-white px-5 py-10 text-center text-sm text-ink-500">
                {{ __('admin.no_records') }}
            </p>
        @else
            <div class="mt-4 overflow-x-auto panel">
                <table class="w-full min-w-[44rem] text-sm">
                    <thead>
                        <tr class="border-b border-hairline bg-ink-50 text-start">
                            <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.enquiry.sender') }}</th>
                            <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('form.subject') }}</th>
                            <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.status') }}</th>
                            <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.enquiry.received') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach ($recent as $enquiry)
                            <tr class="{{ $enquiry->status === 'new' ? 'bg-clay-50/40' : '' }}">
                                <td class="px-4 py-3">
                                    <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-medium text-ink-900 hover:text-clay-600">
                                        {{ $enquiry->name }}
                                    </a>
                                    @if ($enquiry->company)
                                        <span class="block text-xs text-ink-500">{{ $enquiry->company }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-600">
                                    <span class="rounded-sm rounded-md border border-hairline px-1.5 py-0.5 text-[0.625rem] uppercase text-ink-500">
                                        {{ __('admin.enquiry.type_'.$enquiry->type) }}
                                    </span>
                                    <span class="ms-2">{{ Str::limit($enquiry->subject ?: $enquiry->message, 48) }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-xs {{ $enquiry->status === 'new' ? 'font-semibold text-clay-600' : 'text-ink-500' }}">
                                        {{ __('admin.enquiry.'.$enquiry->status) }}
                                    </span>
                                </td>
                                <td class="tabular ltr-run px-4 py-3 text-xs text-ink-500">
                                    {{ $enquiry->created_at->format('Y-m-d H:i') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>

</x-layouts.admin>
