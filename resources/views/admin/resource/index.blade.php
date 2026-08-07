<x-layouts.admin :title="$title">

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-ink-950">{{ $title }}</h1>

        <div class="flex items-center gap-3">
            <form method="GET" class="flex gap-2">
                <label for="q" class="sr-only">{{ __('admin.search_placeholder') }}</label>
                <input id="q" type="search" name="q" value="{{ $search }}"
                       placeholder="{{ __('admin.search_placeholder') }}"
                       class="w-44 border border-ink-300 bg-white px-3 py-2 text-sm focus:border-ink-500 focus:outline-none sm:w-56">
            </form>

            <x-button :href="route('admin.'.$routeName.'.create')" size="sm">
                {{ __('admin.create') }}
            </x-button>
        </div>
    </div>

    @if ($records->isEmpty())
        <p class="mt-6 rounded-lg border border-dashed border-ink-300 bg-white px-5 py-14 text-center text-sm text-ink-500">
            {{ __('admin.no_records') }}
        </p>
    @else
        <div class="mt-6 overflow-x-auto panel">
            <table class="w-full min-w-[42rem] text-sm">
                <thead>
                    <tr class="border-b border-hairline bg-ink-50">
                        @foreach ($columns as $label)
                            <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">
                                {{ $label }}
                            </th>
                        @endforeach
                        <th scope="col" class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-ink-500">
                            {{ __('admin.actions') }}
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-hairline">
                    @foreach ($records as $record)
                        <tr>
                            @foreach ($columns as $attribute => $label)
                                <td class="px-4 py-3 align-top">
                                    @php
                                        // `data_get` handles dotted paths like `category.name`,
                                        // and translatable attributes resolve to the active locale.
                                        $value = data_get($record, $attribute);
                                    @endphp

                                    @if (is_bool($value))
                                        <span class="inline-block px-2 py-0.5 text-[0.6875rem] font-medium
                                                     {{ $value ? 'bg-green-100 text-green-800' : 'bg-ink-100 text-ink-500' }}">
                                            {{ $value ? __('admin.active') : __('admin.inactive_state') }}
                                        </span>
                                    @elseif ($value instanceof \Carbon\CarbonInterface)
                                        <span class="tabular ltr-run text-xs text-ink-500">{{ $value->format('Y-m-d') }}</span>
                                    @elseif ($loop->first)
                                        <a href="{{ route('admin.'.$routeName.'.edit', $record) }}"
                                           class="font-medium text-ink-900 hover:text-clay-600">
                                            {{ Str::limit((string) $value, 64) ?: '—' }}
                                        </a>
                                    @else
                                        <span class="text-ink-600">{{ Str::limit((string) $value, 48) ?: '—' }}</span>
                                    @endif
                                </td>
                            @endforeach

                            <td class="px-4 py-3 text-end align-top">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('admin.'.$routeName.'.edit', $record) }}"
                                       class="text-xs font-medium text-ink-600 hover:text-ink-950">{{ __('admin.edit') }}</a>

                                    @can('delete', $record)
                                        <form method="POST" action="{{ route('admin.'.$routeName.'.destroy', $record) }}"
                                              onsubmit="return confirm(@js(__('admin.confirm_delete')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">
                                                {{ __('admin.delete') }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{ $records->links() }}
    @endif

</x-layouts.admin>
