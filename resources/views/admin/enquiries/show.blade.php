<x-layouts.admin :title="$enquiry->name">

    <a href="{{ route('admin.enquiries.index') }}" class="text-xs text-ink-500 hover:text-ink-900">
        <span class="inline-block rtl:rotate-180" aria-hidden="true">&larr;</span> {{ __('admin.enquiries') }}
    </a>

    <div class="mt-4 grid gap-6 lg:grid-cols-3">

        {{-- ── Message ────────────────────────────────────────────────── --}}
        <div class="lg:col-span-2">
            <div class="border border-hairline bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h1 class="text-lg font-bold text-ink-950">{{ $enquiry->name }}</h1>
                        @if ($enquiry->company)
                            <p class="text-sm text-ink-600">{{ $enquiry->company }}</p>
                        @endif
                    </div>

                    <span class="border border-hairline px-2 py-1 text-[0.6875rem] uppercase tracking-wider text-ink-500">
                        {{ __('admin.enquiry.type_'.$enquiry->type) }}
                    </span>
                </div>

                @if (filled($enquiry->subject))
                    <p class="mt-5 text-sm font-semibold text-ink-900">{{ $enquiry->subject }}</p>
                @endif

                @if (filled($enquiry->message))
                    <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $enquiry->message }}</p>
                @endif

                <a href="mailto:{{ $enquiry->email }}?subject={{ rawurlencode('Re: '.($enquiry->subject ?: 'Your enquiry')) }}"
                   class="mt-6 inline-flex bg-ink-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-clay-600">
                    {{ __('admin.enquiry.reply_by_email') }}
                </a>
            </div>

            {{-- ── Triage ─────────────────────────────────────────────── --}}
            <form method="POST" action="{{ route('admin.enquiries.update', $enquiry) }}"
                  class="mt-6 border border-hairline bg-white p-6">
                @csrf
                @method('PATCH')

                <div class="grid gap-5 sm:grid-cols-2">
                    <div class="flex flex-col gap-1.5">
                        <label for="status" class="text-sm font-medium text-ink-800">{{ __('admin.enquiry.mark_as') }}</label>
                        <select id="status" name="status"
                                class="border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-900 focus:outline-none">
                            @foreach (\App\Models\ContactMessage::STATUSES as $status)
                                <option value="{{ $status }}" @selected(old('status', $enquiry->status) === $status)>
                                    {{ __('admin.enquiry.'.$status) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label for="internal_note" class="text-sm font-medium text-ink-800">{{ __('admin.enquiry.internal_note') }}</label>
                        <textarea id="internal_note" name="internal_note" rows="4"
                                  class="border border-ink-300 px-3 py-2.5 text-sm focus:border-ink-900 focus:outline-none">{{ old('internal_note', $enquiry->internal_note) }}</textarea>
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <x-button type="submit" size="sm">{{ __('admin.save') }}</x-button>

                    @can('delete', $enquiry)
                        <button type="submit"
                                form="delete-enquiry"
                                class="text-sm font-medium text-red-600 hover:text-red-800">
                            {{ __('admin.delete') }}
                        </button>
                    @endcan
                </div>
            </form>

            @can('delete', $enquiry)
                <form id="delete-enquiry" method="POST" action="{{ route('admin.enquiries.destroy', $enquiry) }}"
                      onsubmit="return confirm(@js(__('admin.confirm_delete')))">
                    @csrf
                    @method('DELETE')
                </form>
            @endcan
        </div>

        {{-- ── Metadata ───────────────────────────────────────────────── --}}
        <aside>
            @php
                $facts = array_filter([
                    __('form.email') => $enquiry->email,
                    __('form.phone') => $enquiry->phone,
                    __('form.country') => $enquiry->country_code,
                    __('form.product') => $enquiry->product?->getTranslation('name', 'en'),
                    __('form.quantity') => $enquiry->quantity,
                    __('form.delivery_terms') => $enquiry->delivery_terms,
                    __('nav.language') => strtoupper($enquiry->locale),
                    __('admin.enquiry.received') => $enquiry->created_at->format('Y-m-d H:i'),
                ], 'filled');
            @endphp

            <dl class="divide-y divide-hairline border border-hairline bg-white">
                @foreach ($facts as $label => $value)
                    <div class="px-5 py-3">
                        <dt class="text-[0.6875rem] uppercase tracking-wider text-ink-500">{{ $label }}</dt>
                        <dd class="ltr-run mt-1 break-words text-sm text-ink-900">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if ($enquiry->handled_at)
                <p class="mt-4 text-xs text-ink-500">
                    {{ $enquiry->handler?->name }} — {{ $enquiry->handled_at->format('Y-m-d H:i') }}
                </p>
            @endif
        </aside>
    </div>

</x-layouts.admin>
