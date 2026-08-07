@php
    $hasError = $errors->has('consent');
@endphp

<div class="flex flex-col gap-1.5">
    <label class="flex cursor-pointer items-start gap-3 text-sm text-ink-600">
        <input
            type="checkbox"
            name="consent"
            value="1"
            required
            @checked(old('consent'))
            @if ($hasError) aria-invalid="true" aria-describedby="consent-error" @endif
            class="mt-1 h-4 w-4 shrink-0 border-ink-400 text-clay-600 focus:ring-clay-500/40"
        >
        <span>
            {{ __('form.consent_label') }}
            <a href="{{ route('legal.privacy') }}" class="text-clay-600 underline underline-offset-2">
                {{ __('common.privacy') }}
            </a>
        </span>
    </label>

    @error('consent')
        <p id="consent-error" class="text-xs text-red-600">{{ $message }}</p>
    @enderror
</div>
