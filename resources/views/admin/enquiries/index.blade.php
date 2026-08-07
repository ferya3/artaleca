<x-layouts.admin :title="__('admin.enquiries')">

    <h1 class="text-xl font-bold text-ink-950">{{ __('admin.enquiries') }}</h1>

    <div class="mt-5 flex flex-wrap gap-2">
        <a href="{{ route('admin.enquiries.index') }}"
           class="border px-3 py-1.5 text-xs transition-colors
                  {{ ! $activeStatus && ! $activeType ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-600 hover:border-ink-400' }}">
            {{ __('common.view_all') }}
        </a>

        @foreach (\App\Models\ContactMessage::STATUSES as $status)
            <a href="{{ route('admin.enquiries.index', ['status' => $status]) }}"
               class="border px-3 py-1.5 text-xs transition-colors
                      {{ $activeStatus === $status ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-600 hover:border-ink-400' }}">
                {{ __('admin.enquiry.'.$status) }}
            </a>
        @endforeach

        <span class="mx-1 w-px bg-hairline" aria-hidden="true"></span>

        @foreach (\App\Models\ContactMessage::TYPES as $type)
            <a href="{{ route('admin.enquiries.index', ['type' => $type]) }}"
               class="border px-3 py-1.5 text-xs transition-colors
                      {{ $activeType === $type ? 'border-ink-950 bg-ink-950 text-white' : 'border-hairline text-ink-600 hover:border-ink-400' }}">
                {{ __('admin.enquiry.type_'.$type) }}
            </a>
        @endforeach
    </div>

    @if ($enquiries->isEmpty())
        <p class="mt-6 rounded-lg border border-dashed border-ink-300 bg-white px-5 py-14 text-center text-sm text-ink-500">
            {{ __('admin.no_records') }}
        </p>
    @else
        <div class="mt-6 overflow-x-auto panel">
            <table class="w-full min-w-[48rem] text-sm">
                <thead>
                    <tr class="border-b border-hairline bg-ink-50">
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.enquiry.sender') }}</th>
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('form.message') }}</th>
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.status') }}</th>
                        <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.enquiry.received') }}</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-hairline">
                    @foreach ($enquiries as $enquiry)
                        <tr class="{{ $enquiry->status === 'new' ? 'bg-clay-50/40' : '' }}">
                            <td class="px-4 py-3 align-top">
                                <a href="{{ route('admin.enquiries.show', $enquiry) }}" class="font-medium text-ink-900 hover:text-clay-600">
                                    {{ $enquiry->name }}
                                </a>
                                @if ($enquiry->company)
                                    <span class="block text-xs text-ink-500">{{ $enquiry->company }}</span>
                                @endif
                                <span class="ltr-run block text-xs text-ink-400">{{ $enquiry->email }}</span>
                            </td>

                            <td class="px-4 py-3 align-top">
                                <span class="rounded-sm rounded-md border border-hairline px-1.5 py-0.5 text-[0.625rem] uppercase text-ink-500">
                                    {{ __('admin.enquiry.type_'.$enquiry->type) }}
                                </span>
                                <span class="ltr-run ms-1.5 text-[0.625rem] uppercase text-ink-400">{{ $enquiry->locale }}</span>
                                <p class="mt-1 text-xs text-ink-600">{{ Str::limit($enquiry->subject ?: $enquiry->message, 70) }}</p>
                            </td>

                            <td class="px-4 py-3 align-top text-xs {{ $enquiry->status === 'new' ? 'font-semibold text-clay-600' : 'text-ink-500' }}">
                                {{ __('admin.enquiry.'.$enquiry->status) }}
                            </td>

                            <td class="tabular ltr-run px-4 py-3 align-top text-xs text-ink-500">
                                {{ $enquiry->created_at->format('Y-m-d H:i') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $enquiries->links() }}
    @endif

</x-layouts.admin>
