<x-layouts.admin :title="__('admin.users')">

    <div class="flex items-center justify-between gap-4">
        <h1 class="text-xl font-bold text-ink-950">{{ __('admin.users') }}</h1>
        <x-button :href="route('admin.users.create')" size="sm">{{ __('admin.create') }}</x-button>
    </div>

    <div class="mt-6 overflow-x-auto panel">
        <table class="w-full min-w-[36rem] text-sm">
            <thead>
                <tr class="border-b border-hairline bg-ink-50">
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('form.name') }}</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.email') }}</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">Role</th>
                    <th scope="col" class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.status') }}</th>
                    <th scope="col" class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-ink-500">{{ __('admin.actions') }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-hairline">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.edit', $user) }}" class="font-medium text-ink-900 hover:text-brand-600">{{ $user->name }}</a>
                            @if ($user->last_login_at)
                                <span class="tabular ltr-run block text-xs text-ink-400">{{ $user->last_login_at->format('Y-m-d H:i') }}</span>
                            @endif
                        </td>
                        <td class="ltr-run px-4 py-3 text-ink-600">{{ $user->email }}</td>
                        <td class="px-4 py-3 text-ink-600">{{ $user->role }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 text-[0.6875rem] font-medium
                                         {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-ink-100 text-ink-500' }}">
                                {{ $user->is_active ? __('admin.active') : __('admin.inactive_state') }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-end">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-xs font-medium text-ink-600 hover:text-ink-950">{{ __('admin.edit') }}</a>

                                @can('delete', $user)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                          onsubmit="return confirm(@js(__('admin.confirm_delete')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-800">{{ __('admin.delete') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{ $users->links() }}

</x-layouts.admin>
