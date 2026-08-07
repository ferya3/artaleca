@php
    use App\Support\FormToken;
@endphp

{{--
    Two invisible spam defences, paired:

    1. `website` is a honeypot. It is hidden from sighted users with CSS and
       from assistive technology with aria-hidden + tabindex, so only a script
       filling every input will populate it — and the validator rejects the
       submission outright if it is not empty.

    2. `started_at` is a signed render timestamp. The server measures how long
       the form was on screen; a submission that arrives in under three seconds
       was not typed by a person.

    Together these replace a third-party CAPTCHA: no external request, no
    cookie, no accessibility penalty, and nothing for a visitor to solve.
--}}
<div aria-hidden="true" class="absolute -left-[9999px] h-0 w-0 overflow-hidden" >
    <label for="f-website">Website</label>
    <input id="f-website" type="text" name="website" tabindex="-1" autocomplete="off" value="">
</div>

<input type="hidden" name="started_at" value="{{ FormToken::issue() }}">
