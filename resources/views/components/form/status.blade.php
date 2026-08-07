{{--
    Both messages are live regions, so a screen reader announces the outcome of
    a submission without the user having to hunt for it after the page reloads.
--}}

@if (session('status'))
    <div role="status" class="mb-8 rounded-md border-s-2 border-green-600 bg-green-50 px-5 py-4 text-sm text-green-900">
        {{ session('status') }}
    </div>
@endif

@if ($errors->any())
    <div role="alert" class="mb-8 rounded-md border-s-2 border-red-600 bg-red-50 px-5 py-4 text-sm text-red-900">
        <p class="font-semibold">{{ __('form.has_errors') }}</p>
        <ul class="mt-2 list-disc space-y-1 ps-4">
            @foreach ($errors->unique() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
